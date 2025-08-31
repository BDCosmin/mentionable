<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GiphyService
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function search(string $query, int $limit = 5): array
    {
        $response = $this->client->request('GET', 'https://api.giphy.com/v1/gifs/search', [
            'query' => [
                'api_key' => $this->apiKey,
                'q' => $query,
                'limit' => $limit,
            ],
        ]);

        $data = $response->toArray();

        return array_map(fn($gif) => $gif['images']['original']['url'], $data['data']);
    }
}