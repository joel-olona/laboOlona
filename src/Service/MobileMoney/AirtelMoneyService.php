<?php

namespace App\Service\MobileMoney;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;

class AirtelMoneyService
{
    public function __construct(
        private HttpClientInterface $client, 
        private string $clientId, 
        private string $clientSecret, 
        private string $apiUrl
    ){}

    private function authenticate()
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

            return $data['data']['key']; 

        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            throw new \Exception('Erreur lors de la récupération de la clé de chiffrement : ' . $e->getMessage());
        }
    }

    private function generateSignatureAndKey($payload)
    {
        $rsaPublicKey = $this->encryptionKey();
        $formattedKey = "-----BEGIN PUBLIC KEY-----\n" .
                        chunk_split($rsaPublicKey, 64, "\n") .
                        "-----END PUBLIC KEY-----";
    
        // Générer une clé AES 256 bits et un IV 128 bits
        $aesKey = openssl_random_pseudo_bytes(32);
        $iv = openssl_random_pseudo_bytes(16);
    
        if ($aesKey === false || $iv === false) {
            throw new \Exception('Échec de la génération de la clé AES ou de l’IV.');
        }
    
        // Encoder la clé AES et l'IV en Base64
        $aesKeyBase64 = base64_encode($aesKey);
        $ivBase64 = base64_encode($iv);
    
        // Chiffrer le payload avec AES-256-CBC
        $encryptedPayload = openssl_encrypt(json_encode($payload), 'aes-256-cbc', $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($encryptedPayload === false) {
            throw new \Exception('Échec du chiffrement AES du payload.');
        }
    
        // Encoder le payload chiffré en Base64
        $encryptedPayloadBase64 = base64_encode($encryptedPayload);
    
        // Concaténer la clé AES et l'IV avec ":"
        $keyIv = $aesKeyBase64 . ':' . $ivBase64;
    
        // Chiffrer la clé AES et l'IV avec RSA
        if (!openssl_public_encrypt($keyIv, $encryptedKeyIv, $formattedKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new \Exception('Échec du chiffrement RSA de la clé AES.');
        }
    
        // Encoder la clé chiffrée en Base64
        $encryptedKeyIvBase64 = base64_encode($encryptedKeyIv);
    
        return [
            'x-signature' => $encryptedPayloadBase64,
            'x-key' => $encryptedKeyIvBase64,
        ];
    }

    private function encryptPin($pin)
    {
        $rsaPublicKey = $this->encryptionKey();
        $formattedKey = "-----BEGIN PUBLIC KEY-----\n" .
                        chunk_split($rsaPublicKey, 64, "\n") .
                        "-----END PUBLIC KEY-----";
    
        // Chiffrement du PIN avec OpenSSL
        openssl_public_encrypt($pin, $encryptedPin, $formattedKey, OPENSSL_PKCS1_PADDING);

        // Conversion du PIN chiffré en base64 pour le transmettre en toute sécurité
        return base64_encode($encryptedPin);
    }

    public function payments($payload)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/merchant/v1/payments/';
        $security = $this->generateSignatureAndKey($payload);

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => '*/* ',
            'X-Country' => 'MG',
            'X-Currency' => 'MGA',
            'x-signature' => $security['x-signature'],
            'x-key' => $security['x-key'],
            'Content-Type' => 'application/json'
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'json' => $payload,
            ]);
            $content = $response->getContent(); 
        } catch (
            TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface $exception
        ) {
            $content = $exception;
        }

        return $content;
    }

    public function kyc($msisdn)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/standard/v1/users/'. $msisdn;

        $headers = [
            'Accept' => '*/* ',
            'X-Country' => 'MG',
            'X-Currency' => 'MGA',
            'Authorization' => 'Bearer ' . $accessToken
        ];
        dump($url, $headers);

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

    public function enquiryBalance()
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/standard/v1/users/balance';

        $headers = [
            'Accept' => '*/* ',
            'X-Country' => 'MG',
            'X-Currency' => 'MGA',
            'Authorization' => 'Bearer ' . $accessToken
        ];
        dump($url, $headers);

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

    public function enquiry($id)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/standard/v1/payments/'. $id;

        $headers = [
            'Accept' => '*/* ',
            'X-Country' => 'MG',
            'X-Currency' => 'MGA',
            'Authorization' => 'Bearer ' . $accessToken
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

    public function disbursements($payload)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/standard/v1/disbursements';
        $payload['pin'] = $this->encryptPin($payload['pin']);

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => '*/* ',
            'X-Country' => 'MG',
            'X-Currency' => 'MGA',
            'Content-Type' => 'application/json'
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'json' => $payload,
            ]);
            $content = $response->getContent(); 
        } catch (
            TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface $exception
        ) {
            $content = $exception;
        }

        return $content;
    }

    public function callback(Request $request)
    {
        $url = $this->apiUrl . '/callback_path';

        $headers = [
            'Content-Type' => 'application/json'
        ];

        try {
            $response = $this->client->request('POST', $url, [
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