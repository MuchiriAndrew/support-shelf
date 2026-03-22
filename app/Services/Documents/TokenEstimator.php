<?php

namespace App\Services\Documents;

class TokenEstimator
{
    /**
     * Estimate the token count for a piece of text.
     */
    public function estimate(string $text): int
    {
        $normalized = trim($text);

        if ($normalized === '') {
            return 0;
        }

        return max(1, (int) ceil(str_word_count($normalized) * 1.35));
    }
}
