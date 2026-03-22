<?php

namespace App\Services\Documents;

class DocumentNormalizer
{
    /**
     * Normalize extracted text so it is consistent for chunking and storage.
     */
    public function normalize(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;

        $lines = preg_split("/\n/", $text) ?: [];
        $normalizedLines = [];
        $previousBlank = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                if (! $previousBlank) {
                    $normalizedLines[] = '';
                }

                $previousBlank = true;

                continue;
            }

            $normalizedLines[] = $line;
            $previousBlank = false;
        }

        $normalized = trim(implode("\n", $normalizedLines));

        return preg_replace("/\n{3,}/", "\n\n", $normalized) ?? $normalized;
    }
}
