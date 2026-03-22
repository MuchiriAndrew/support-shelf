<?php

namespace App\Services\Crawling;

use App\Models\CrawlRun;
use App\Models\Source;
use App\Services\Documents\DocumentIngestionService;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;
use Throwable;

class SupportSiteCrawler
{
    public function __construct(
        protected HttpBrowser $browser,
        protected DocumentIngestionService $documentIngestionService,
    ) {
    }

    /**
     * Crawl a support source and persist extracted documents.
     */
    public function crawlSource(Source $source, string $triggeredBy = 'manual', ?callable $onProgress = null): CrawlRun
    {
        if (! $source->url) {
            throw new RuntimeException('The selected source does not have a crawlable URL.');
        }

        $maxDepth = $this->resolveMaxDepth($source);
        $maxPages = $this->resolveMaxPages($source);

        $run = $source->crawlRuns()->create([
            'status' => 'running',
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
            'metadata' => [
                'seed_url' => $source->url,
                'max_depth' => $maxDepth,
                'max_pages' => $maxPages,
            ],
        ]);

        try {
            $summary = $this->performCrawl($source, $run, $maxDepth, $maxPages, $onProgress);

            $run->fill([
                'status' => 'completed',
                'finished_at' => now(),
                'pages_discovered' => $summary['pages_discovered'],
                'pages_processed' => $summary['pages_processed'],
                'documents_upserted' => $summary['documents_upserted'],
                'metadata' => array_merge($run->metadata ?? [], [
                    'last_url' => $summary['last_url'],
                    'stopped_reason' => $summary['stopped_reason'],
                ]),
            ])->save();

            $source->forceFill([
                'last_crawled_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $run->fill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }

        return $run->fresh();
    }

    /**
     * @return array{pages_discovered: int, pages_processed: int, documents_upserted: int, last_url: string|null, stopped_reason: string|null}
     */
    protected function performCrawl(Source $source, CrawlRun $run, int $maxDepth, int $maxPages, ?callable $onProgress = null): array
    {
        $queue = [[$this->normalizeUrl($source->url), 0]];
        $visited = [];
        $discovered = [];
        $processedPages = 0;
        $upsertedDocuments = 0;
        $lastUrl = null;
        $stoppedReason = null;
        $progressEvery = max(1, (int) config('crawling.progress_every', 5));

        while ($queue !== []) {
            if (count($visited) >= $maxPages) {
                $stoppedReason = 'max_pages_reached';
                break;
            }

            [$url, $depth] = array_shift($queue);

            if (! is_string($url) || isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;
            $lastUrl = $url;

            $crawler = $this->fetch($url);
            $response = $this->browser->getResponse();

            if ($response === null || $response->getStatusCode() >= 400) {
                continue;
            }

            $contentType = strtolower((string) $response->getHeader('content-type'));

            if ($contentType !== '' && ! Str::contains($contentType, ['text/html', 'application/xhtml+xml'])) {
                continue;
            }

            $title = $this->extractTitle($crawler, $url);
            $content = $this->extractPrimaryContent($crawler, $source->content_selector);
            $isRelevant = $this->pageLooksRelevant($source, $title, $content, $url);

            if ($isRelevant && mb_strlen($content) >= 80) {
                $result = $this->documentIngestionService->ingestText(
                    $source,
                    $title,
                    'support_page',
                    $content,
                    [
                        'canonical_url' => $url,
                        'metadata' => [
                            'crawl_depth' => $depth,
                            'source_url' => $source->url,
                        ],
                    ],
                );

                if ($result['created'] || $result['updated']) {
                    $upsertedDocuments++;
                }

                $processedPages++;
            }

            if ($depth < $maxDepth && ($isRelevant || $depth === 0)) {
                foreach ($this->discoverLinks($crawler, $url, $source) as $link) {
                    if (! isset($visited[$link]) && ! isset($discovered[$link])) {
                        $discovered[$link] = true;
                        $queue[] = [$link, $depth + 1];
                    }
                }
            }

            if (count($visited) === 1 || count($visited) % $progressEvery === 0) {
                $progress = $this->syncRunProgress($run, $discovered, count($visited), $processedPages, $upsertedDocuments, $lastUrl, $stoppedReason);

                if ($onProgress !== null) {
                    $onProgress($progress);
                }
            }
        }

        $progress = $this->syncRunProgress($run, $discovered, count($visited), $processedPages, $upsertedDocuments, $lastUrl, $stoppedReason);

        if ($onProgress !== null) {
            $onProgress($progress);
        }

        return [
            'pages_discovered' => count($discovered) + 1,
            'pages_processed' => $processedPages,
            'documents_upserted' => $upsertedDocuments,
            'last_url' => $lastUrl,
            'stopped_reason' => $stoppedReason,
        ];
    }

    protected function fetch(string $url): Crawler
    {
        $delay = (int) config('crawling.delay_ms', 0);

        if ($delay > 0) {
            usleep($delay * 1000);
        }

        return $this->browser->request('GET', $url);
    }

    protected function extractTitle(Crawler $crawler, string $fallbackUrl): string
    {
        foreach (['h1', 'title'] as $selector) {
            $nodes = $crawler->filter($selector);

            if ($nodes->count() === 0) {
                continue;
            }

            $title = trim($nodes->first()->text('', true));

            if ($title !== '') {
                return Str::limit($title, 180, '');
            }
        }

        return $fallbackUrl;
    }

    protected function extractPrimaryContent(Crawler $crawler, ?string $customSelector = null): string
    {
        $selectors = $customSelector
            ? [$customSelector]
            : ['main', 'article', '[role="main"]', '.article-content', '.entry-content', '.content', '.main-content', 'body'];

        foreach ($selectors as $selector) {
            try {
                $nodes = $crawler->filter($selector);
            } catch (Throwable) {
                continue;
            }

            if ($nodes->count() === 0) {
                continue;
            }

            $segments = array_values(array_filter(
                array_map(
                    fn (Crawler $node): string => trim($node->text('', true)),
                    $nodes->each(fn (Crawler $node): Crawler => $node),
                ),
                fn (string $segment): bool => $segment !== '',
            ));

            $content = trim(implode("\n\n", array_unique($segments)));

            if ($content !== '') {
                return $content;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    protected function discoverLinks(Crawler $crawler, string $currentUrl, Source $source): array
    {
        $host = strtolower((string) ($source->domain ?: parse_url($source->url ?? '', PHP_URL_HOST)));
        $keywords = $this->resolveRelevantKeywords($source);

        return array_values(array_unique(array_filter(array_map(function (Crawler $node) use ($currentUrl, $host, $keywords): ?string {
            $href = trim((string) $node->attr('href'));

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'javascript:')) {
                return null;
            }

            $absoluteUrl = $this->normalizeUrl(UriResolver::resolve($href, $currentUrl));
            $absoluteHost = strtolower((string) parse_url($absoluteUrl, PHP_URL_HOST));

            if ($absoluteHost === '' || ! $this->hostMatches($absoluteHost, $host)) {
                return null;
            }

            if (preg_match('/\.(pdf|jpg|jpeg|png|gif|svg|zip|mp4|mp3)$/i', (string) parse_url($absoluteUrl, PHP_URL_PATH))) {
                return null;
            }

            if ($keywords !== []) {
                $linkText = Str::lower(trim($node->text('', true)));
                $linkContext = Str::lower($absoluteUrl.' '.$linkText);

                $matchesKeyword = false;

                foreach ($keywords as $keyword) {
                    if (str_contains($linkContext, $keyword)) {
                        $matchesKeyword = true;
                        break;
                    }
                }

                if (! $matchesKeyword) {
                    return null;
                }
            }

            return $absoluteUrl;
        }, $crawler->filter('a[href]')->each(fn (Crawler $node): Crawler => $node)))));
    }

    protected function hostMatches(string $host, string $expectedHost): bool
    {
        return $host === $expectedHost || str_ends_with($host, '.'.$expectedHost);
    }

    protected function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return rtrim($url, '/');
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        $normalizedPath = rtrim($path, '/');

        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        return sprintf('%s://%s%s%s', $scheme, $host, $normalizedPath, $query);
    }

    protected function resolveMaxDepth(Source $source): int
    {
        $metadata = $source->metadata ?? [];

        if (isset($metadata['max_depth']) && is_numeric($metadata['max_depth'])) {
            return max(0, (int) $metadata['max_depth']);
        }

        return (int) config('crawling.max_depth', 2);
    }

    protected function resolveMaxPages(Source $source): int
    {
        $metadata = $source->metadata ?? [];

        if (isset($metadata['max_pages']) && is_numeric($metadata['max_pages'])) {
            return max(1, (int) $metadata['max_pages']);
        }

        return max(1, (int) config('crawling.max_pages', 40));
    }

    protected function pageLooksRelevant(Source $source, string $title, string $content, string $url): bool
    {
        $keywords = $this->resolveRelevantKeywords($source);

        if ($keywords === []) {
            return true;
        }

        $haystack = Str::lower(implode(' ', [
            $title,
            $url,
            Str::limit($content, 4000, ''),
        ]));

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function resolveRelevantKeywords(Source $source): array
    {
        $rawDomainTokens = preg_split('/[^a-z0-9]+/i', Str::lower((string) $source->domain)) ?: [];
        $rawNameTokens = preg_split('/[^a-z0-9]+/i', Str::lower($source->name)) ?: [];
        $rawPathTokens = preg_split('/[^a-z0-9]+/i', Str::lower((string) parse_url((string) $source->url, PHP_URL_PATH))) ?: [];

        $domainTokens = array_values(array_filter($rawDomainTokens, fn (string $token): bool => $token !== ''));
        $stopwords = $this->crawlKeywordStopwords();

        $keywords = array_values(array_unique(array_filter(
            [...$rawPathTokens, ...$rawNameTokens],
            fn (string $token): bool => $token !== ''
                && strlen($token) >= 4
                && ! in_array($token, $domainTokens, true)
                && ! in_array($token, $stopwords, true),
        )));

        return $keywords;
    }

    /**
     * @return list<string>
     */
    protected function crawlKeywordStopwords(): array
    {
        return [
            'support',
            'center',
            'help',
            'home',
            'index',
            'docs',
            'doc',
            'guide',
            'guides',
            'manual',
            'manuals',
            'official',
            'knowledge',
            'base',
            'store',
            'learn',
            'article',
            'articles',
            'product',
            'products',
        ];
    }

    /**
     * @param  array<string, bool>  $discovered
     */
    protected function syncRunProgress(
        CrawlRun $run,
        array $discovered,
        int $visitedPages,
        int $processedPages,
        int $upsertedDocuments,
        ?string $lastUrl,
        ?string $stoppedReason,
    ): array {
        $metadata = $run->metadata ?? [];

        $run->forceFill([
            'pages_discovered' => count($discovered) + 1,
            'pages_processed' => $processedPages,
            'documents_upserted' => $upsertedDocuments,
            'metadata' => array_merge($metadata, array_filter([
                'last_url' => $lastUrl,
                'stopped_reason' => $stoppedReason,
            ], fn (mixed $value): bool => $value !== null)),
        ])->save();

        return [
            'pages_visited' => $visitedPages,
            'pages_discovered' => count($discovered) + 1,
            'pages_processed' => $processedPages,
            'documents_upserted' => $upsertedDocuments,
            'last_url' => $lastUrl,
            'stopped_reason' => $stoppedReason,
        ];
    }
}
