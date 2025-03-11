<?php

namespace App\Controller\TableauDeBord;

use App\Entity\Logs\ActivityLog;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MobileMoney\AirtelMoneyService;
use App\Service\MobileMoney\MvolaService;
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
        $payload = [
            "reference" => "Testing transaction",
            "subscriber" => [
                "country" => "MG",
                "currency" => "MGA",
                "msisdn" => "332046888"
            ],
            "transaction" => [
                "amount" => "100",
                "country" => "MG",
                "currency" => "MGA",
                "id" => "testid12"
            ]
        ];

        $response = json_decode($this->airtelMoneyService->payments($payload), true);

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
        $payload = [
            'X-CorrelationID' => '123456789', 
            'partnerMSISDN' => '0343500003', 
            'requestingOrganisationTransactionReference' => 'ABC123', 
            'originalTransactionReference' => 'AZERTY', 
            'partnerName' => 'olona_talents', 
            'amount' => '100', 
            'description' => 'credit_olona_talents', 
            'customerMSISDN' => "0340268554", 
        ];

        $response = json_decode($this->mvolaService->mvolaPayment($payload), true);

        return $this->json(
            $response, 
            200, 
            [], 
        );
    }

}
