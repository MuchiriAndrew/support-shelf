<?php

namespace App\Providers;

use App\Contracts\VectorStore;
use App\Services\VectorStores\WeaviateVectorStore;
use Smalot\PdfParser\Parser;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HttpBrowser::class, function (): HttpBrowser {
            $requestTimeout = (float) config('crawling.timeout', 20);
            $connectTimeout = (float) config('crawling.connect_timeout', 10);

            $options = [
                'headers' => [
                    'User-Agent' => config('crawling.user_agent'),
                ],
                // Symfony's generic client options do not expose a separate
                // connect-timeout setting across all transports. We use the
                // shorter of the configured connect/overall timeouts for idle
                // socket timing, and keep the full request budget in max_duration.
                'timeout' => $connectTimeout > 0 ? min($requestTimeout, $connectTimeout) : $requestTimeout,
                'max_duration' => $requestTimeout,
            ];

            return new HttpBrowser(HttpClient::create($options));
        });

        $this->app->singleton(Parser::class);
        $this->app->singleton(VectorStore::class, WeaviateVectorStore::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
