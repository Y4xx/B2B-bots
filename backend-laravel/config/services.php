<?php

return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'price_basic' => env('STRIPE_PRICE_BASIC', 'price_basic'),
        'price_pro' => env('STRIPE_PRICE_PRO', 'price_pro'),
        'price_enterprise' => env('STRIPE_PRICE_ENTERPRISE', 'price_enterprise'),
    ],

    'python' => [
        'scraper_url' => env('PYTHON_SCRAPER_URL', 'http://localhost:8001'),
        'indexer_url' => env('PYTHON_INDEXER_URL', 'http://localhost:8002'),
        'rag_url' => env('PYTHON_RAG_URL', 'http://localhost:8003'),
    ],

    'vector_db' => [
        'driver' => env('VECTOR_DB_DRIVER', 'qdrant'),
        'qdrant' => [
            'host' => env('QDRANT_HOST', 'localhost'),
            'port' => env('QDRANT_PORT', 6333),
            'api_key' => env('QDRANT_API_KEY'),
        ],
        'pinecone' => [
            'api_key' => env('PINECONE_API_KEY'),
            'environment' => env('PINECONE_ENVIRONMENT'),
            'index_name' => env('PINECONE_INDEX_NAME', 'saasbot'),
        ],
    ],
];
