<?php
// src/Service/GeoService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeoService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getCoordinates(string $address): ?array
    {
        $response = $this->client->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'address' => $address,
                'key' => $this->apiKey
            ]
        ]);

        $data = $response->toArray();

        if (!empty($data['results'])) {
            return $data['results'][0]['geometry']['location'];
        }

        return null;
    }
    
}
