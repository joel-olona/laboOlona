<?php

namespace App\Service\MobileMoney;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

class MvolaService
{
    public function __construct(
        private HttpClientInterface $client, 
        private UrlGeneratorInterface $urlGenerator,
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
            'Authorization' => 'Bearer ' . $accessToken,
            'Version' => '1.0',
            'X-CorrelationID' => $payload['X-CorrelationID'],
            // 'X-Callback-URL' => $this->urlGenerator->generate('mvola_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'UserLanguage' => 'FR',
            'UserAccountIdentifier' => $payload['partnerMSISDN'],
            'partnerName' => $payload['partnerName'],
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no_cache',
        ];

        $data = [
            'amount' => $payload['amount'],
            'currency' => 'Ar',
            'descriptionText' => $payload['description'],
            'requestDate' => $date->format("Y-m-d\TH:i:s.v\Z"),
            'requestingOrganisationTransactionReference' => $payload['requestingOrganisationTransactionReference'],
            'originalTransactionReference' => $payload['originalTransactionReference'],
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

    public function transactionStatus(string $serverCorrelationId)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/'. $serverCorrelationId;
        
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Version' => '1.0',
            'X-CorrelationID' => '1234567890',
            'UserLanguage' => 'mg',
            'UserAccountIdentifier' => 'msisdn;0343500003',
            'partnerName' => 'OlonaTalents',
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no_cache',
        ];

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => $headers,
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