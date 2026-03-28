<?php

namespace App\Services\Embeddings;

use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class OpenAiEmbeddingService
{
    public function isConfigured(): bool
    {
        return filled(config('openai.api_key')) && filled($this->model());
    }

    public function model(): ?string
    {
        $model = config('assistant.models.embeddings');

        return is_string($model) && trim($model) !== '' ? trim($model) : null;
    }

    /**
     * @param  list<string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedTexts(array $texts): array
    {
        $texts = array_values(array_filter(
            array_map(static fn (string $text): string => trim($text), $texts),
            static fn (string $text): bool => $text !== '',
        ));

        if ($texts === []) {
            return [];
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException('OpenAI embeddings are not configured. Set OPENAI_API_KEY and OPENAI_EMBEDDING_MODEL.');
        }

        $response = OpenAI::embeddings()->create([
            'model' => $this->model(),
            'input' => $texts,
        ]);

        return array_map(
            static fn ($embedding): array => $embedding->embedding,
            $response->embeddings,
        );
    }

    /**
     * @return array<int, float>
     */
    public function embedText(string $text): array
    {
        $vectors = $this->embedTexts([$text]);

        return $vectors[0] ?? [];
    }
}
