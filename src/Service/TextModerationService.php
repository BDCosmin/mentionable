<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TextModerationService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function analyze(string $text): array
    {
        try {
            $response = $this->client->request('POST', 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze', [
                'query' => ['key' => $this->apiKey],
                'json' => [
                    'comment' => ['text' => $text],
                    'languages' => ['en'],
                    'requestedAttributes' => [
                        'TOXICITY' => new \stdClass(),
                        'INSULT' => new \stdClass(),
                    ],
                ],
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            // În loc de mesaj generic, arată eroarea exactă
            throw new \RuntimeException('Perspective API error: ' . $e->getMessage());
        }

    }

}