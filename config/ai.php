<?php
return [
    'provider' => env('AI_PROVIDER', 'openai'),
    'key'      => env('AI_API_KEY'),
    'model'    => env('AI_MODEL', 'gpt-4o-mini'),
];
