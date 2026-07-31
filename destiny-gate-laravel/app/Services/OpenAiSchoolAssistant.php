<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiSchoolAssistant
{
    public function isConfigured(): bool
    {
        return filled(config('services.openai.key'));
    }

    public function generateHeadmasterInsights(array $context): array
    {
        $result = $this->requestJson(
            'You are an experienced school operations advisor. Review the supplied school dashboard data and return concise, practical guidance for a headmaster. Keep recommendations realistic, data-driven, and suitable for a secondary school.',
            [
                'task' => 'Generate headmaster insights',
                'instructions' => [
                    'Return valid JSON only.',
                    'Provide a short executive summary.',
                    'Provide 3 risks or watch items.',
                    'Provide 3 recommended actions.',
                    'Provide a confidence note grounded in the available data.',
                ],
                'context' => $context,
            ],
            0.4
        );

        return [
            'summary' => (string) ($result['summary'] ?? ''),
            'risks' => array_values(array_map('strval', $result['risks'] ?? [])),
            'recommended_actions' => array_values(array_map('strval', $result['recommended_actions'] ?? [])),
            'confidence_note' => (string) ($result['confidence_note'] ?? ''),
            'model' => (string) config('services.openai.model'),
        ];
    }

    public function draftAnnouncement(array $context): array
    {
        $result = $this->requestJson(
            'You are a school communications assistant. Draft clear, professional announcements for a headmaster. Keep the tone appropriate for parents, students, and staff, and avoid exaggerated claims.',
            [
                'task' => 'Draft a school announcement',
                'instructions' => [
                    'Return valid JSON only.',
                    'Include a title.',
                    'Include announcement content in 2 to 5 short paragraphs.',
                    'Include 3 short key points.',
                    'Keep the draft ready for review by school leadership.',
                ],
                'context' => $context,
            ],
            0.7
        );

        return [
            'title' => (string) ($result['title'] ?? ''),
            'content' => (string) ($result['content'] ?? ''),
            'key_points' => array_values(array_map('strval', $result['key_points'] ?? [])),
            'model' => (string) config('services.openai.model'),
        ];
    }

    private function requestJson(string $systemPrompt, array $payload, float $temperature = 0.4): array
    {
        $apiKey = config('services.openai.key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI is not configured. Set OPENAI_API_KEY in your environment.');
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = (int) config('services.openai.timeout', 30);

        try {
            $response = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->timeout($timeout)
                ->post('chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => $temperature,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
                    ],
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                'OpenAI request failed: ' . $exception->response->json('error.message', $exception->getMessage()),
                previous: $exception
            );
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->pluck('text')
                ->filter()
                ->implode("\n");
        }

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenAI returned an empty response.');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON content.');
        }

        return $decoded;
    }
}
