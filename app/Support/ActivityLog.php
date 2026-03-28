<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class ActivityLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function debug(string $message, array $context = []): void
    {
        static::write('debug', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        static::write('info', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        static::write('warning', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $message, array $context = []): void
    {
        static::write('error', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected static function write(string $level, string $message, array $context = []): void
    {
        $channel = (string) config('assistant.logging.channel', 'stack');
        $context = array_filter(array_replace([
            'domain' => 'supportshelf',
            'environment' => app()->environment(),
        ], $context), static fn (mixed $value): bool => $value !== null);

        try {
            Log::channel($channel)->log($level, $message, $context);
        } catch (Throwable) {
            Log::log($level, $message, array_merge($context, [
                'fallback_channel' => config('logging.default'),
                'intended_channel' => $channel,
            ]));
        }
    }
}
