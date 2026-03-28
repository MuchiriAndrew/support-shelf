<?php

return [

    'brand' => [
        'name' => env('ASSISTANT_NAME', env('SUPPORT_ASSISTANT_NAME', 'SupportShelf')),
        'tagline' => env('ASSISTANT_TAGLINE', env('SUPPORT_ASSISTANT_TAGLINE', 'Build a private assistant from your own documents and websites.')),
    ],

    'documents' => [
        'disk' => env('ASSISTANT_DOCUMENT_DISK', env('SUPPORT_DOCUMENT_DISK', 'local')),
        'path' => env('ASSISTANT_DOCUMENT_PATH', env('SUPPORT_DOCUMENT_PATH', 'source-documents')),
    ],

    'models' => [
        'responses' => env('OPENAI_RESPONSES_MODEL'),
        'embeddings' => env('OPENAI_EMBEDDING_MODEL'),
    ],

    'retrieval' => [
        'top_k' => (int) env('ASSISTANT_RETRIEVAL_TOP_K', env('SUPPORT_RETRIEVAL_TOP_K', 8)),
    ],

    'logging' => [
        'channel' => env('ASSISTANT_LOG_CHANNEL', env('SUPPORT_LOG_CHANNEL', 'supportshelf')),
    ],

];
