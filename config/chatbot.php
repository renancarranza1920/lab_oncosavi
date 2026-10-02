<?php

return [
    'enabled' => env('CHATBOT_ENABLED', true),
    'timezone' => 'America/El_Salvador',
    'max_days' => 366,
    'max_rows' => 50,
    'default_rows' => 25,
    'requests_per_minute' => 40,
    'ai' => [
        'base_url' => env('CHATBOT_OLLAMA_URL', 'http://ollama:11434'),
        'model' => env('CHATBOT_LOCAL_MODEL', 'qwen3:4b'),
        'timeout' => 100,
        'context' => 4096,
    ],
];
