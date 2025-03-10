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
        // dd($this->airtelMoneyService->checkBalance());
        $payload = json_encode([
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
                "id" => "test_id"
            ]
        ]);
        dump("payload : ", $payload);
        dd("response : ", $this->airtelMoneyService->payments($payload));

        return $this->render('tableau_de_bord/candidat/index.html.twig', []);
    }

    #[Route('/mvola', name: 'app_mobile_money_mvola')]
    public function mvola(
        Request $request
    ): Response
    {
        $payload = [
            'X-CorrelationID' => '123456789', 
            'partnerMSISDN' => '0380842696', // Votre numéro de téléphone enregistré avec MVola
            'partnerName' => 'olona-talents.com', 
            'amount' => '10000', 
            'description' => 'Achat crédit Olona Talents', 
            'customerMSISDN' => '0340268554', 
        ];
        dd($this->mvolaService->mvolaPayment($payload));
        dd($this->airtelMoneyService->payments($payload));

        return $this->render('tableau_de_bord/candidat/index.html.twig', []);
    }

}
