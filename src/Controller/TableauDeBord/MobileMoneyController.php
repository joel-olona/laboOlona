<?php

namespace App\Controller\TableauDeBord;

use Symfony\Component\Uid\Uuid;
use App\Entity\Logs\ActivityLog;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\MobileMoney\MvolaService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MobileMoney\AirtelMoneyService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/mobile-payment')]

class MobileMoneyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private AirtelMoneyService $airtelMoneyService,
        private MvolaService $mvolaService,
    ){}

    #[Route('/airtel-money', name: 'app_mobile_money_airtel')]
    public function airtel(
        Request $request
    ): Response
    {

        $uuid = Uuid::v4()->toRfc4122();
        $payload = [
            "reference" => "Testing transaction",
            "subscriber" => [
                "country" => "MG",
                "currency" => "MGA",
                "msisdn" => "333798105"
            ],
            "transaction" => [
                "amount" => "300",
                "country" => "MG",
                "currency" => "MGA",
                "id" => $uuid
            ]
        ];

        $data = [
            'payee' => [
                'msisdn' => '332046888',
                'wallet_type' => 'NORMAL',
            ],
            'reference' => 'AB41',
            'pin' => '2627',
            'transaction' => [
                'amount' => 1000,
                'id' => 'AB141',
                'type' => 'B2C',
            ],
        ];

        // $response = json_decode($this->airtelMoneyService->enquiry(), true);
        // $response = json_decode($this->airtelMoneyService->kyc("332046888"), true);
        $response = json_decode($this->airtelMoneyService->payments($payload), true);
        // $response = json_decode($this->airtelMoneyService->disbursements($data), true);
        dd($response);

        return $this->json(
            $response, 
            200, 
            [], 
        );
    }

    #[Route('/mvola', name: 'app_mobile_money_mvola')]
    public function mvola(
        Request $request
    ): Response
    {
        $uuid = Uuid::v4()->toRfc4122();
        $timestamp = time();
        $payload = [
            'X-CorrelationID' => $uuid, 
            'partnerMSISDN' => '0343500003', 
            'requestingOrganisationTransactionReference' => 'order_' . $timestamp, 
            'originalTransactionReference' => 'AZERTY', 
            'partnerName' => 'olona_talents', 
            'amount' => '100', 
            'description' => 'credit_olona_talents', 
            'customerMSISDN' => '0343500004', 
        ];

        $response = json_decode($this->mvolaService->mvolaPayment($payload), true);

        return $this->json(
            $response, 
            200, 
            [], 
        );
    }
    
    #[Route('/mvola/status/{uuid}', name: 'app_mobile_money_mvola_status')]
    public function mvolaStatus(
        Request $request,
        string $uuid
    ): Response
    {
        $response = json_decode($this->mvolaService->transactionStatus($uuid), true);

        return $this->json(
            $response, 
            200, 
            [], 
        );
    }

    #[Route('/mvola/callback', name: 'mvola_callback', methods: ['PUT'])]
    public function callback(Request $request): Response
    {
        // Décoder le contenu JSON de la requête
        $data = json_decode($request->getContent(), true);
        dd($data);

        // Vérifier les données reçues
        if (isset($data['status']) && isset($data['serverCorrelationId'])) {
            // Logique métier: Mettre à jour la base de données ou traiter le statut
            // Par exemple, vous pouvez mettre à jour l'état d'une transaction
            $status = $data['status'];
            $serverCorrelationId = $data['serverCorrelationId'];
            
            // Exemple de retour en fonction du statut reçu
            if ($status === 'success') {
                // Code pour mettre à jour la transaction comme réussie
            } elseif ($status === 'failed') {
                // Code pour gérer une transaction échouée
            }
            
            // Répondre à la requête MVola avec un succès
            return $this->json(['message' => 'Notification received and processed successfully'], Response::HTTP_OK);
        }

        // Répondre avec une erreur si les données ne sont pas correctes
        return $this->json(['error' => 'Invalid data received'], Response::HTTP_BAD_REQUEST);
    }

}
