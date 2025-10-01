<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdviceService
{
    /**
     * Generate AI advice cards from structured context.
     *
     * @param array $context
     * @return array items[] schema
     */
    public function generate(array $context): array
    {
        $provider = config('ai.provider', env('AI_PROVIDER', 'openai'));
        $model    = config('ai.model', env('AI_MODEL', 'gpt-4o-mini'));
        $apiKey   = config('ai.key', env('AI_API_KEY'));

        // Single JSON object schema with "items" array inside.
        $schema = <<<JSON
Return a single JSON object with this exact shape:
{
  "items": [
    {
      "title": "string",
      "category": "string or null",
      "insight": "string",
      "why": "string",
      "actions": ["string", "..."],
      "severity": "low" | "medium" | "high"
    }
  ]
}
No markdown. No extra fields.
JSON;

        $system = <<<SYS
You are a concise, practical personal finance coach.
Given structured spending context, produce 1–5 advice items.
Be specific to the data, avoid generic filler.
$schema
SYS;

        $user = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $rawJson = null;

            if ($provider === 'openai') {
                // OpenAI Chat Completions
                $resp = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    // Force JSON so we can parse reliably
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $user],
                    ],
                ])->throw()->json();

                $rawJson = data_get($resp, 'choices.0.message.content', null);

            } elseif ($provider === 'anthropic') {
                // Anthropic Messages
                $resp = Http::withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                    ])->post('https://api.anthropic.com/v1/messages', [
                        'model' => $model,
                        'max_tokens' => 1024,
                        'temperature' => 0.2,
                        'system' => $system,
                        'messages' => [
                            ['role' => 'user', 'content' => $user],
                        ],
                    ])->throw()->json();

                $blocks = data_get($resp, 'content', []);
                $combined = '';
                foreach ($blocks as $b) {
                    if (($b['type'] ?? '') === 'text') {
                        $combined .= $b['text'];
                    }
                }
                $rawJson = $combined ?: '{"items":[]}';
            } else {
                // Ollama (local). We request JSON output.
                $ollama = rtrim(env('OLLAMA_URL', 'http://localhost:11434'), '/');
                $resp = Http::post($ollama . '/api/chat', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user',   'content' => $user],
                    ],
                    'options' => ['temperature' => 0.3],
                    'format'  => 'json',
                    'stream'  => false,
                ])->throw()->json();

                $rawJson = data_get($resp, 'message.content', null);
            }

            if (!$rawJson) {
                return [];
            }

            $decoded = json_decode($rawJson, true);
            if (!is_array($decoded)) {
                return [];
            }

            // Accept either {"items":[...]} or plain array (back-compat)
            if (array_key_exists('items', $decoded) && is_array($decoded['items'])) {
                return $decoded['items'];
            }
            return $decoded;

        } catch (\Throwable $e) {
            Log::warning('AI advice failed', [
                'provider' => $provider,
                'model'    => $model,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }
}
