<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class EventDescriptionAISuggester
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private string $model;
    private ?LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        string $huggingfaceApiKey,
        ?LoggerInterface $logger = null
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $huggingfaceApiKey;
        $this->model = 'distilgpt2'; // Default model
        $this->logger = $logger;
    }

    public function generateEventDescription(string $name, string $location, string $category): ?string
    {
        // If API key is empty, return a fallback description
        if (empty($this->apiKey)) {
            $this->logger?->warning('No HuggingFace API key provided, using fallback description');
            return $this->getFallbackDescription($name, $location, $category);
        }

        try {
            $this->logger?->info('Generating description for event', [
                'name' => $name,
                'location' => $location,
                'category' => $category
            ]);

            $prompt = <<<EOT
Write a compelling and detailed description for the following event:
- Event Name: $name
- Location: $location
- Category: $category

The description should be engaging, informative, and approximately 3-4 sentences long. Focus on what attendees can expect and why they should attend.
EOT;

            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/' . $this->model, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $prompt,
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger?->error('HuggingFace API returned non-200 status code', [
                    'status_code' => $statusCode,
                    'response' => $response->getContent(false)
                ]);
                return $this->getFallbackDescription($name, $location, $category);
            }

            $data = $response->toArray(false);

            // Different models return different response formats
            if (isset($data[0]['generated_text'])) {
                $text = $data[0]['generated_text'];
                // Remove the prompt from the response
                $text = str_replace($prompt, '', $text);
                return trim($text);
            }

            if (isset($data['generated_text'])) {
                $text = $data['generated_text'];
                // Remove the prompt from the response
                $text = str_replace($prompt, '', $text);
                return trim($text);
            }

            $this->logger?->warning('Unexpected API response format', ['data' => $data]);
            return $this->getFallbackDescription($name, $location, $category);

        } catch (\Exception $e) {
            $this->logger?->error('Error generating event description', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return a fallback description instead of throwing an exception
            return $this->getFallbackDescription($name, $location, $category);
        }
    }

    private function getFallbackDescription(string $name, string $location, string $category): string
    {
        $templates = [
            "Join us for \"{$name}\", an exciting {$category} event taking place in {$location}. This event promises to be a memorable experience with activities, entertainment, and opportunities to connect with others who share your interests.",

            "Experience \"{$name}\" - the premier {$category} event in {$location}! Attendees will enjoy a carefully curated program designed to inspire, entertain, and create lasting memories. Don't miss this opportunity to be part of something special.",

            "We're thrilled to present \"{$name}\" in {$location}, a must-attend {$category} event for enthusiasts and newcomers alike. Prepare for an unforgettable experience filled with highlights, special moments, and connections that will last beyond the event itself."
        ];

        // Return a random template
        return $templates[array_rand($templates)];
    }
}
