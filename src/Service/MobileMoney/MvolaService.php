<?php

namespace App\Service\MobileMoney;

use App\Service\User\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MvolaService
{
    public function __construct(
        #[Autowire('@monolog.logger.mvola')]
        private LoggerInterface $logger,
        private UserService $userService,
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

    public function paymentsCurl(array $payload)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/';
        $date = new \DateTime("now", new \DateTimeZone("UTC"));
        $this->logInteraction('Début de la transaction MVola', [
            'user' => $this->userService->getCurrentUser(),
            'endpoint' => $url,
            'payload' => $payload,
        ]);
        
        // Préparation des headers
        $headers = [
            'accept: */*',
            'Version: 1.0',
            'X-CorrelationID: ' . $payload['X-CorrelationID'],
            'Cache-Control: no_cache',
            'Content-Type: application/json',
            'UserLanguage: FR',
            'UserAccountIdentifier: ' . $payload['partnerMSISDN'],
            'partnerName: ' . $payload['partnerName'],
            'Authorization: Bearer ' . $accessToken,
        ];

        // Préparation du corps de la requête
        $data = [
            'amount' => (string) $payload['amount'],
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

        // Initialisation de cURL
        $ch = curl_init();

        // Configuration des options cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        try {
            // Exécution de la requête
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new \RuntimeException('cURL error: ' . curl_error($ch));
            }

            // Traitement de la réponse
            $content = json_decode($response, true);
            $this->logInteraction('Réponse MVola', [
                'response' => $content,
            ]);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $content = $response;
            }
            
            // Vous pourriez vérifier $httpCode ici pour gérer différents codes de statut

        } catch (\Exception $exception) {
            $content = $exception;
        } finally {
            // Fermeture de la session cURL
            curl_close($ch);
        }

        return $content;
    }

    public function transactionStatus(string $serverCorrelationId)
    {
        $accessToken = $this->authenticate();
        $url = $this->apiUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/' . $serverCorrelationId;

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Version: 1.0',
            'X-CorrelationID: ' . $serverCorrelationId,
            'UserLanguage: mg',
            'UserAccountIdentifier: msisdn;0380842696',
            'partnerName: olona-talents.com',
            'Content-Type: application/json',
            'Cache-Control: no_cache',
        ];

        // Log de la requête
        $this->logInteraction('Vérification du statut MVola', [
            'url' => $url,
            'X-CorrelationID' => $serverCorrelationId,
        ]);

        // Initialisation de cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new \RuntimeException('cURL error: ' . curl_error($ch));
            }

            $content = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $content = $response;
            }

            $this->logInteraction('Statut MVola reçu', [
                'httpCode' => $httpCode,
                'response' => $content,
            ]);
        } catch (\Exception $exception) {
            $content = $exception;
            $this->logInteraction('Erreur lors de la vérification du statut MVola', [
                'error' => $exception->getMessage(),
            ]);
        } finally {
            curl_close($ch);
        }

        return $content;
    }

    public function logInteraction(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }
}