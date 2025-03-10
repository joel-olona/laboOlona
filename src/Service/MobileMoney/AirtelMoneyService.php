<?php

namespace App\Service\MobileMoney;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

class AirtelMoneyService
{
    private $client;
    private $clientId;
    private $clientSecret;
    private $apiUrl;

    public function __construct(HttpClientInterface $client, string $clientId, string $clientSecret, string $apiUrl)
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->apiUrl = $apiUrl;
    }

    public function authenticate()
    {
        $response = $this->client->request('POST', $this->apiUrl . '/auth/oauth2/token', [
            'body' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ]
        ]);

        $data = $response->toArray();
        return $data['access_token'] ?? null;
    }

    public function checkBalance()
    {
        // Example on how to call a specific API endpoint
        $accessToken = $this->authenticate();
        $response = $this->client->request('GET', $this->apiUrl . '/standard/v1/users/balance', [
            'headers' => [
                'Accept' => '*/*',
                'X-Country' => 'MG',
                'X-Currency' => 'MGA',
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);

        return $response->toArray();
    }
    
    private function encryptionKey()
    {
        $accessToken = $this->authenticate();
        try {
            $response = $this->client->request('GET', $this->apiUrl . '/v1/rsa/encryption-keys', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-Country' => 'MG', 
                    'X-Currency' => 'MGA', 
                ]
            ]);

            $data = $response->toArray();
            dump("encriptionKey : ", $data);
            return $data['data']['key']; 

        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            throw new \Exception('Erreur lors de la récupération de la clé de chiffrement : ' . $e->getMessage());
        }
    }

    public function payments($payload)
    {
        $accessToken = $this->authenticate();
        $security = $this->generateSignatureAndKey($payload);

        $headers = [
            'Accept' => '*/* ',
            'Content-Type' => 'application/json',
            'X-Country' => 'MG',
            'X-Currency' => 'MGA',
            'Authorization' => 'Bearer ' . $accessToken,
            ' x-signature' => $security['x-signature'],
            ' x-key' => $security['x-key']
        ];
        dump("headers : " ,$headers);

        try {
            $response = $this->client->request('POST', 'https://openapiuat.airtel.africa/merchant/v2/payments/', array(
              'headers' => $headers,
              'json' => $payload,
              )
            );

            $content = $response->getContent(); 
        } catch (
            TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface $exception
        ) {
            $content = $exception;
        }

        return $content;
    }

    private function generateSignatureAndKey($payload)
    {
        $rsaPublicKey = $this->encryptionKey();
        $formattedKey = "-----BEGIN PUBLIC KEY-----\n" .
                        chunk_split($rsaPublicKey, 64, "\n") .
                        "-----END PUBLIC KEY-----";
    
        // 1️⃣ Générer une clé AES 256 bits et un IV 128 bits
        $aesKey = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);
    
        if ($aesKey === false || $iv === false) {
            throw new \Exception('Échec de la génération de la clé AES ou de l’IV.');
        }
    
        // 2️⃣ Encoder la clé AES et l'IV en Base64
        $aesKeyBase64 = base64_encode($aesKey);
        $ivBase64 = base64_encode($iv);
    
        // 3️⃣ Chiffrer le payload avec AES-256-CBC
        $encryptedPayload = openssl_encrypt($payload, 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($encryptedPayload === false) {
            throw new \Exception('Échec du chiffrement AES du payload.');
        }
    
        // 4️⃣ Encoder le payload chiffré en Base64
        $encryptedPayloadBase64 = base64_encode($encryptedPayload);
    
        // 5️⃣ Concaténer la clé AES et l'IV avec ":"
        $keyIv = $aesKeyBase64 . ':' . $ivBase64;
    
        // 6️⃣ Chiffrer la clé AES et l'IV avec RSA
        if (!openssl_public_encrypt($keyIv, $encryptedKeyIv, $formattedKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new \Exception('Échec du chiffrement RSA de la clé AES.');
        }
    
        // 7️⃣ Encoder la clé chiffrée en Base64
        $encryptedKeyIvBase64 = base64_encode($encryptedKeyIv);
    
        // Retourner les headers pour la requête sécurisée
        return [
            'x-signature' => $encryptedPayloadBase64,
            'x-key' => $encryptedKeyIvBase64,
        ];
    }
    
}