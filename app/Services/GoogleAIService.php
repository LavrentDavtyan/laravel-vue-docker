<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAIService
{
    protected string $apiKey;
    protected string $model;


    public function __construct()
    {
        $this->apiKey = env('AI_API_KEY', '');
        $this->model = env('AI_MODEL', 'gemini-2.5-flash');
    }


    public function generateContent(string $prompt)
    {

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
        ]);

        if ($response->failed()) {
            Log::error('GenerateContent error', ['body' => $response->body()]);
            return ['error' => $response->body()];
        }

        return $response->json();
    }
}
