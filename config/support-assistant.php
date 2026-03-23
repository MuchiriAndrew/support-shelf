<?php

return [

    'brand' => [
        'name' => env('SUPPORT_ASSISTANT_NAME', 'SupportShelf AI'),
        'tagline' => env('SUPPORT_ASSISTANT_TAGLINE', 'Instant product support grounded in manuals and store policies.'),
    ],

    'documents' => [
        'disk' => env('SUPPORT_DOCUMENT_DISK', 'local'),
        'path' => env('SUPPORT_DOCUMENT_PATH', 'source-documents'),
    ],

    'models' => [
        'responses' => env('OPENAI_RESPONSES_MODEL'),
        'embeddings' => env('OPENAI_EMBEDDING_MODEL'),
    ],

    'retrieval' => [
        'top_k' => (int) env('SUPPORT_RETRIEVAL_TOP_K', 8),
    ],

    'logging' => [
        'channel' => env('SUPPORT_LOG_CHANNEL', 'supportshelf'),
    ],

];
