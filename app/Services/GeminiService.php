<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function generateCourseTitles(string $category): array
    {
        $apiKey = env('GEMINI_API_KEY');

        try {
            $response = Http::timeout(30)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    [
                                        'text' => "Generate exactly 5 YouTube playlist search queries for educational courses about: {$category}. Return ONLY a valid JSON array of strings. No explanation. No markdown. No backticks. Example: [\"Python for beginners\", \"Python crash course\"]"
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.7,
                            'maxOutputTokens' => 1000,
                        ]
                    ]
                );

            if ($response->failed()) {
                \Log::error('Gemini failed: ' . $response->body());
                return $this->fallbackTitles($category);
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if (!$text) {
                return $this->fallbackTitles($category);
            }

            $text   = preg_replace('/```json\s*|\s*```/', '', trim($text));
            $titles = json_decode($text, true);

            return is_array($titles) ? $titles : $this->fallbackTitles($category);
        } catch (\Exception $e) {
            \Log::error('Gemini exception: ' . $e->getMessage());
            return $this->fallbackTitles($category);
        }
    }

    private function fallbackTitles(string $category): array
    {
        return [
            "{$category} full course",
            "{$category} tutorial for beginners",
            "learn {$category} complete guide",
            "{$category} masterclass",
            "{$category} step by step",
        ];
    }
}
