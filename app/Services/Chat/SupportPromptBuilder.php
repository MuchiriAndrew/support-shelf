<?php

namespace App\Services\Chat;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SupportPromptBuilder
{
    public function instructions(): string
    {
        return <<<PROMPT
        You are SupportShelf AI, a helpful product support assistant for an e-commerce support workspace.
        Answer clearly, practically, and in a friendly tone.
        Use the provided support context as your source of truth.
        If the answer is not supported by the provided context, say that you could not verify it from the available support materials.
        Do not invent policies, specs, compatibility claims, troubleshooting steps, or warranty terms.
        Prefer direct answers first, then short steps or caveats when useful.
        PROMPT;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $matches
     */
    public function developerMessage(Collection $matches, string $transcript = ''): string
    {
        $context = $matches
            ->take((int) config('support-assistant.retrieval.top_k', 8))
            ->values()
            ->map(function (array $match, int $index): string {
                $title = data_get($match, 'document.title', 'Untitled source');
                $source = data_get($match, 'document.source', 'Support source');
                $url = data_get($match, 'document.canonical_url', 'n/a');
                $excerpt = Str::limit(trim((string) ($match['content'] ?? '')), 2200, '...');

                return sprintf(
                    "[%d] %s\nSource: %s\nURL: %s\nExcerpt:\n%s",
                    $index + 1,
                    $title,
                    $source,
                    $url,
                    $excerpt,
                );
            })
            ->implode("\n\n");

        if ($context === '') {
            $context = 'No relevant support context was retrieved for this turn.';
        }

        $transcript = trim($transcript);
        $transcriptBlock = $transcript !== ''
            ? "Conversation transcript so far:\n{$transcript}\n\n"
            : '';

        return <<<PROMPT
        {$transcriptBlock}Retrieved support context:
        {$context}
        PROMPT;
    }
}
