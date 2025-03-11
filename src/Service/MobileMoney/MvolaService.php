<?php

namespace App\Service\MobileMoney;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

class MvolaService
{
    public function __construct(
        private HttpClientInterface $client, 
        private string $clientId, 
        private string $clientSecret, 
        private string $apiUrl, 
        private string $scope
    ){}

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

        return $data['access_token'] ?? null;
    }

    public function mvolaPayment(array $payload)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/';
        $date = new \DateTime("now", new \DateTimeZone("UTC"));
        $headers = [
            'Version' => '1.0',
            'X-CorrelationID' => $payload['X-CorrelationID'],
            'UserLanguage' => 'mg',
            'UserAccountIdentifier' => $payload['partnerMSISDN'],
            'partnerName' => $payload['partnerName'],
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'Cache-Control' => 'no_cache',
        ];

        $data = [
            'amount' => $payload['amount'],
            'currency' => 'Ar',
            'descriptionText' => $payload['description'],
            'requestingOrganisationTransactionReference' => $payload['requestingOrganisationTransactionReference'],
            'originalTransactionReference' => $payload['originalTransactionReference'],
            'requestDate' => $date->format("Y-m-d\TH:i:s.v\Z"),
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
}