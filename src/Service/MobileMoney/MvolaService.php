<?php

namespace App\Service\MobileMoney;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

class MvolaService
{
    private $client;
    private $clientId;
    private $clientSecret;
    private $apiUrl;
    private $scope;

    public function __construct(HttpClientInterface $client, string $clientId, string $clientSecret, string $apiUrl, string $scope)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->apiUrl = $apiUrl;
        $this->scope = $scope;
    }

    public function authenticate()
    {
        $response = $this->client->request('POST', $this->apiUrl . '/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Cache-Control' => 'no-cache',
            ],
            'body' => [
                'grant_type' => 'client_credentials',
                'scope' => $this->scope,
            ]
        ]);

        $data = $response->toArray();
        dump($data);
        return $data['access_token'] ?? null;
    }

    public function mvolaPayment(array $payload)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/';
        $headers = [
            'Version' => '1.0',
            'X-CorrelationID' => $payload['X-CorrelationID'],
            'UserLanguage' => 'mg',
            'UserAccountIdentifier' => 'msisdn;' . $payload['partnerMSISDN'],
            'partnerName' => $payload['partnerName'],
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'Cache-Control' => 'no-cache',
        ];

        $data = [
            'amount' => $payload['amount'],
            'currency' => 'Ar',
            'descriptionText' => $payload['description'],
            'requestingOrganisationTransactionReference' => '',
            'requestDate' => '',
            'originalTransactionReference' => '',
            'debitParty' => [
                [
                    'key' => 'msisdn',
                    'value' => $payload['customerMSISDN'],
                ]
            ],
            'creditParty' => [
                [
                    'key' => 'msisdn',
                    'value' => $payload['partnerMSISDN'],
                ]
            ],
            'metadata' => [
                [
                    'key' => 'partnerName',
                    'value' => $payload['partnerName'],
                ],
                [
                    'key' => 'fc',
                    'value' => 'USD',
                ],
                [
                    'key' => 'amountFc',
                    'value' => '1',
                ]
            ]
        ];     
        dump($headers, $data);

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'json' => $data,
            ]);
            $content = $response->getContent(); 
        } catch (
            TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface $exception
        ) {
            $content = $exception;
        }
    
        return $content;
    }

    // Add more methods for each API endpoint you plan to use 
}