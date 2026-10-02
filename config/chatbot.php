<?php

return [
    'enabled' => env('CHATBOT_ENABLED', true),
    'timezone' => 'America/El_Salvador',
    'max_days' => 366,
    'max_rows' => 20,
    'ai' => [
        'base_url' => env('CHATBOT_OLLAMA_URL', 'http://ollama:11434'),
        'model' => env('CHATBOT_LOCAL_MODEL', 'qwen3:1.7b'),
        'timeout' => 80,
    ],
];
