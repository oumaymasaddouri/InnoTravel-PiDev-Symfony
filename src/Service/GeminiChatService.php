<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiChatService
{
    private HttpClientInterface $client;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function sendMessage(string $userMessage): ?string
    {
        $response = $this->client->request('POST', $this->apiUrl . '?key=' . "AIzaSyC3C0Fvrs-wFZrM-XlNuSHn51Kq-YUi_cA", [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userMessage]
                        ]
                    ]
                ]
            ]
        ]);

        $data = $response->toArray(false);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
}
