<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdviceService
{
    public function __construct(GoogleAIService $googleAIService)
    {
        $this->googleAIService = $googleAIService;
    }
    /**
     * Generate AI advice cards from structured context.
     *
     * @param array $context
     * @return array items[] schema
     */
    public function generate(array $context): array
    {
        $provider = env('AI_PROVIDER', 'gemini');

        try {
            if ($provider == 'gemini') {

                $response = $this->googleAIService->generateContent($this->buildPrompt($context));

                // Try to extract the raw text from Gemini’s structured response
                $raw = data_get($response, 'candidates.0.content.parts.0.text');

                if (!$raw) {
                    Log::warning('Gemini returned empty response', ['response' => $response]);
                    return ['items' => []];
                }

                $cleanJson = preg_replace('/^```json|```$/m', '', trim($raw));

                // Decode JSON
                $decoded = json_decode($cleanJson, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['items'])) {
                    return $decoded;
                }

                Log::warning('Failed to decode Gemini JSON', [
                    'error' => json_last_error_msg(),
                    'raw' => $cleanJson,
                ]);
            } 
        } catch (\Throwable $e) {
            Log::error('Advice generation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function buildPrompt(array $context): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return <<<PROMPT
You are a financial assistant. Based on this JSON context of the user's spending and budgets, generate personalized spending advices .

Context:
{$json}

Return response in JSON format like this:

{
  "items": [
    {
      "title": "Short headline",
      "category": "optional-category-or-null",
      "insight": "Explain what was found",
      "why": "Explain why it matters",
      "actions": ["Action 1", "Action 2", "Action 3"],
      "severity": "low|medium|high"
    }
  ]
}
PROMPT;
    }


    
}
