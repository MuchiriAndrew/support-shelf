<?php

return [

    'default' => env('VECTOR_STORE_DRIVER', 'weaviate'),

    'stores' => [
        'weaviate' => [
            'url' => env('WEAVIATE_URL', 'http://127.0.0.1:8081'),
            'api_key' => env('WEAVIATE_API_KEY'),
            'collection' => env('WEAVIATE_COLLECTION', 'support_chunks'),
            'timeout' => (float) env('WEAVIATE_TIMEOUT', 10),
        ],
    ],

];
