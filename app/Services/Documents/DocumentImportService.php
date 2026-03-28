<?php

namespace App\Services\Documents;

use App\Models\Source;
use App\Models\User;
use App\Services\Ingestion\SourceRegistryService;
use App\Support\ActivityLog;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Symfony\Component\DomCrawler\Crawler;

class DocumentImportService
{
    public function __construct(
        protected DocumentIngestionService $documentIngestionService,
        protected SourceRegistryService $sourceRegistry,
        protected Parser $pdfParser,
    ) {
    }

    /**
     * Import an uploaded document into storage and normalize it.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{document: \App\Models\Document, created: bool, updated: bool, chunks_count: int}
     */
    public function importUploadedFile(User $user, UploadedFile $file, array $attributes = []): array
    {
        $disk = (string) config('assistant.documents.disk', 'local');
        $directory = trim((string) config('assistant.documents.path', 'source-documents'), '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'txt');
        $filename = $this->makeFilename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), $extension);

        ActivityLog::info('Knowledge document upload started', [
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'extension' => $extension,
            'size_bytes' => $file->getSize(),
            'disk' => $disk,
        ]);

        try {
            $storagePath = Storage::disk($disk)->putFileAs($directory, $file, $filename);

            if (! is_string($storagePath)) {
                throw new RuntimeException('Unable to store the uploaded document.');
            }

            ActivityLog::debug('Knowledge document upload stored', [
                'original_filename' => $file->getClientOriginalName(),
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
            ]);

            $result = $this->ingestStoredFile($user, $disk, $storagePath, $extension, $attributes, $file->getClientOriginalName());

            ActivityLog::info('Knowledge document upload completed', [
                'user_id' => $user->id,
                'document_id' => $result['document']->id,
                'document_title' => $result['document']->title,
                'chunks_count' => $result['chunks_count'],
                'storage_path' => $storagePath,
            ]);

            return $result;
        } catch (\Throwable $exception) {
            ActivityLog::error('Knowledge document upload failed', [
                'user_id' => $user->id,
                'original_filename' => $file->getClientOriginalName(),
                'disk' => $disk,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    /**
     * Import a local file path into the configured document storage.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{document: \App\Models\Document, created: bool, updated: bool, chunks_count: int}
     */
    public function importPath(User $user, string $path, array $attributes = []): array
    {
        if (! is_file($path)) {
            ActivityLog::warning('Knowledge document path import skipped because the file was missing', [
                'user_id' => $user->id,
                'path' => $path,
            ]);

            throw new RuntimeException("The document path [{$path}] does not exist.");
        }

        $disk = (string) config('assistant.documents.disk', 'local');
        $directory = trim((string) config('assistant.documents.path', 'source-documents'), '/');
        $filename = $this->makeFilename(pathinfo($path, PATHINFO_FILENAME), strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'txt'));

        ActivityLog::info('Knowledge document path import started', [
            'user_id' => $user->id,
            'path' => $path,
            'disk' => $disk,
        ]);

        try {
            $storagePath = Storage::disk($disk)->putFileAs($directory, new File($path), $filename);

            if (! is_string($storagePath)) {
                throw new RuntimeException('Unable to copy the document into application storage.');
            }

            ActivityLog::debug('Knowledge document path copied into storage', [
                'path' => $path,
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
            ]);

            $result = $this->ingestStoredFile($user, $disk, $storagePath, strtolower(pathinfo($storagePath, PATHINFO_EXTENSION)), $attributes, basename($path));

            ActivityLog::info('Knowledge document path import completed', [
                'user_id' => $user->id,
                'path' => $path,
                'document_id' => $result['document']->id,
                'document_title' => $result['document']->title,
                'chunks_count' => $result['chunks_count'],
            ]);

            return $result;
        } catch (\Throwable $exception) {
            ActivityLog::error('Knowledge document path import failed', [
                'user_id' => $user->id,
                'path' => $path,
                'disk' => $disk,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{document: \App\Models\Document, created: bool, updated: bool, chunks_count: int}
     */
    protected function ingestStoredFile(User $user, string $disk, string $storagePath, string $extension, array $attributes, string $originalFilename): array
    {
        $source = $this->resolveSource($user, $attributes);
        $documentType = (string) ($attributes['document_type'] ?? $this->guessDocumentType($extension));
        $title = $this->resolveTitle($attributes['title'] ?? null, $storagePath);
        $text = $this->extractText($disk, $storagePath, $extension);

        ActivityLog::debug('Knowledge document extracted into text', [
            'user_id' => $user->id,
            'original_filename' => $originalFilename,
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
            'document_type' => $documentType,
            'source_id' => $source?->id,
            'text_length' => mb_strlen($text),
        ]);

        return $this->documentIngestionService->ingestText(
            $user,
            $source,
            $title,
            $documentType,
            $text,
            [
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'metadata' => [
                    'imported_via' => 'file_upload',
                    'original_filename' => $originalFilename,
                ],
            ],
        );
    }

    protected function resolveSource(User $user, array $attributes): ?Source
    {
        $sourceName = trim((string) ($attributes['source_name'] ?? ''));

        if ($sourceName === '') {
            return null;
        }

        return $this->sourceRegistry->registerUploadedSource($user, $sourceName, [
            'category' => 'uploaded_document',
        ]);
    }

    protected function resolveTitle(mixed $title, string $storagePath): string
    {
        $title = is_string($title) ? trim($title) : '';

        if ($title !== '') {
            return $title;
        }

        return Str::title(str_replace(['-', '_'], ' ', pathinfo($storagePath, PATHINFO_FILENAME)));
    }

    protected function guessDocumentType(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'pdf_document',
            'html', 'htm' => 'web_page',
            'md', 'markdown' => 'markdown_document',
            default => 'text_document',
        };
    }

    protected function makeFilename(string $base, string $extension): string
    {
        $slug = Str::slug($base);

        if ($slug === '') {
            $slug = 'document';
        }

        return sprintf('%s-%s.%s', $slug, now()->format('YmdHis'), $extension);
    }

    protected function extractText(string $disk, string $storagePath, string $extension): string
    {
        return match ($extension) {
            'pdf' => $this->pdfParser->parseFile(Storage::disk($disk)->path($storagePath))->getText(),
            'html', 'htm' => $this->extractHtmlText(Storage::disk($disk)->get($storagePath), $storagePath),
            'txt', 'md', 'markdown' => Storage::disk($disk)->get($storagePath),
            default => throw new RuntimeException("Unsupported file type [{$extension}] for document import."),
        };
    }

    protected function extractHtmlText(string $html, string $storagePath): string
    {
        $crawler = new Crawler($html, $storagePath);

        foreach (['main', 'article', '.content', 'body'] as $selector) {
            $nodes = $crawler->filter($selector);

            if ($nodes->count() === 0) {
                continue;
            }

            $text = trim($nodes->first()->text('', true));

            if ($text !== '') {
                return $text;
            }
        }

        return trim(strip_tags($html));
    }
}
