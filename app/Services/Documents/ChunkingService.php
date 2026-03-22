<?php

namespace App\Services\Documents;

class ChunkingService
{
    public function __construct(
        protected TokenEstimator $tokenEstimator,
    ) {
    }

    /**
     * Chunk a normalized document into retrieval-sized sections.
     *
     * @return array<int, array{content: string, token_estimate: int}>
     */
    public function chunk(string $text, int $targetTokens = 700, int $overlapTokens = 120): array
    {
        $paragraphs = array_values(array_filter(
            array_map('trim', preg_split("/\n{2,}/", trim($text)) ?: []),
            fn (string $paragraph): bool => $paragraph !== '',
        ));

        if ($paragraphs === []) {
            return [];
        }

        $chunks = [];
        $buffer = [];

        foreach ($paragraphs as $paragraph) {
            foreach ($this->splitParagraphIfNeeded($paragraph, $targetTokens) as $segment) {
                $candidate = [...$buffer, $segment];
                $candidateTokens = $this->tokenEstimator->estimate(implode("\n\n", $candidate));

                if ($buffer !== [] && $candidateTokens > $targetTokens) {
                    $chunks[] = $this->makeChunk($buffer);
                    $buffer = $this->overlapBuffer($buffer, $overlapTokens);
                }

                $buffer[] = $segment;
            }
        }

        if ($buffer !== []) {
            $chunks[] = $this->makeChunk($buffer);
        }

        return array_values(array_filter($chunks, fn (array $chunk): bool => trim($chunk['content']) !== ''));
    }

    /**
     * @param  list<string>  $paragraphs
     * @return array{content: string, token_estimate: int}
     */
    protected function makeChunk(array $paragraphs): array
    {
        $content = implode("\n\n", $paragraphs);

        return [
            'content' => $content,
            'token_estimate' => $this->tokenEstimator->estimate($content),
        ];
    }

    /**
     * @param  list<string>  $buffer
     * @return list<string>
     */
    protected function overlapBuffer(array $buffer, int $overlapTokens): array
    {
        $carry = [];

        while ($buffer !== []) {
            $segment = array_pop($buffer);

            if (! is_string($segment)) {
                continue;
            }

            array_unshift($carry, $segment);

            if ($this->tokenEstimator->estimate(implode("\n\n", $carry)) >= $overlapTokens) {
                break;
            }
        }

        return $carry;
    }

    /**
     * Split oversized paragraphs by sentences or words if needed.
     *
     * @return list<string>
     */
    protected function splitParagraphIfNeeded(string $paragraph, int $targetTokens): array
    {
        if ($this->tokenEstimator->estimate($paragraph) <= $targetTokens) {
            return [$paragraph];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
        $segments = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            if ($sentence === '') {
                continue;
            }

            $candidate = trim($buffer === '' ? $sentence : "{$buffer} {$sentence}");

            if ($buffer !== '' && $this->tokenEstimator->estimate($candidate) > $targetTokens) {
                $segments[] = $buffer;
                $buffer = $sentence;

                continue;
            }

            $buffer = $candidate;
        }

        if ($buffer !== '') {
            $segments[] = $buffer;
        }

        return $this->splitLongSegmentsByWords($segments, $targetTokens);
    }

    /**
     * @param  list<string>  $segments
     * @return list<string>
     */
    protected function splitLongSegmentsByWords(array $segments, int $targetTokens): array
    {
        $normalized = [];

        foreach ($segments as $segment) {
            if ($this->tokenEstimator->estimate($segment) <= $targetTokens) {
                $normalized[] = $segment;

                continue;
            }

            $words = preg_split('/\s+/', $segment) ?: [$segment];
            $buffer = [];

            foreach ($words as $word) {
                $candidate = implode(' ', [...$buffer, $word]);

                if ($buffer !== [] && $this->tokenEstimator->estimate($candidate) > $targetTokens) {
                    $normalized[] = implode(' ', $buffer);
                    $buffer = [$word];

                    continue;
                }

                $buffer[] = $word;
            }

            if ($buffer !== []) {
                $normalized[] = implode(' ', $buffer);
            }
        }

        return $normalized;
    }
}
