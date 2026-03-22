<?php

return [

    'user_agent' => env('CRAWLER_USER_AGENT', 'SupportShelfBot/1.0'),

    'timeout' => (int) env('CRAWLER_TIMEOUT', 20),

    'connect_timeout' => (int) env('CRAWLER_CONNECT_TIMEOUT', 10),

    'delay_ms' => (int) env('CRAWLER_DELAY_MS', 750),

    'max_depth' => (int) env('CRAWLER_MAX_DEPTH', 2),

    'max_pages' => (int) env('CRAWLER_MAX_PAGES', 40),

    'progress_every' => (int) env('CRAWLER_PROGRESS_EVERY', 5),

];
