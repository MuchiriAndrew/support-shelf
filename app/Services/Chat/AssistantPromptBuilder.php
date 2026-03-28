<?php

namespace App\Services\Chat;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssistantPromptBuilder
{
    public function instructions(User $user): string
    {
        $assistantName = $user->assistantDisplayName();
        $customInstructions = trim((string) $user->assistant_instructions);
        $customBlock = $customInstructions !== ''
            ? "\nCustom assistant instructions:\n{$customInstructions}\n"
            : '';

        return <<<PROMPT
        You are {$assistantName}, a helpful AI assistant inside a user-owned private knowledge workspace.
        Answer clearly, practically, and in a friendly tone.
        Use the provided context as your source of truth.
        If the answer is not supported by the provided context, say that you could not verify it from the available uploaded materials.
        Do not invent facts, instructions, policies, or claims that are not supported by the retrieved context.
        Prefer a direct answer first, then short steps or caveats when useful.{$customBlock}
        PROMPT;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $matches
     */
    public function developerMessage(Collection $matches, string $transcript = ''): string
    {
        $context = $matches
            ->take((int) config('assistant.retrieval.top_k', 8))
            ->values()
            ->map(function (array $match, int $index): string {
                $title = data_get($match, 'document.title', 'Untitled source');
                $source = data_get($match, 'document.source', 'Knowledge source');
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
            $context = 'No relevant knowledge-base context was retrieved for this turn.';
        }

        $transcript = trim($transcript);
        $transcriptBlock = $transcript !== ''
            ? "Conversation transcript so far:\n{$transcript}\n\n"
            : '';

        return <<<PROMPT
        {$transcriptBlock}Retrieved context:
        {$context}
        PROMPT;
    }
}
