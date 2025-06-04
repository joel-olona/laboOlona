<?php 

namespace App\Manager\MobileMoney;

use App\Entity\User;
use App\Entity\Finance\Devise;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Entity\Logs\ActivityLog;
use App\Entity\EntrepriseProfile;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Service\MobileMoney\MvolaService;
use App\Service\MobileMoney\AirtelMoneyService;

class MobileMoneyManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private AirtelMoneyService $airtelMoneyService,
        private MvolaService $mvolaService,
    )
    {}

    public function initAirtelMoney(Transaction $transaction, Order $order)
    {
        $uuid = Uuid::v4()->toRfc4122();
        $amount = 300;
        $payload = [
            "reference" => "Testing transaction",
            "subscriber" => [
                "country" => "MG",
                "currency" => "MGA",
                "msisdn" => "331101199",
            ],
            "transaction" => [
                "amount" => $amount,
                "country" => "MG",
                "currency" => "MGA",
                "id" => $uuid
            ]
        ];

        $response = $this->airtelMoneyService->payments($payload);
        
        $statusCode = $response['status_code'] ?? null;
        if (
            !$response['error'] &&
            isset($response['response']['status']) &&
            isset($response['response']['data']['transaction']['id'])
        ) {
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transaction->setReference($response['response']['data']['transaction']['id']);
            $transaction->setToken($uuid);
            $this->em->persist($transaction);
            $this->em->flush();
        
            $order->setStatus(Order::STATUS_PROCESSING);
            $this->em->persist($order);
            $this->em->flush();
        
            return [
                'success' => true,
                'error' => false,
                'message' => 'Transaction en cours de traitement.',
                'status_code' => $statusCode,
                'data' => $response['response']
            ];
        } else {
            $errorMessage = 'Échec de la transaction avec l’API Airtel Money.';
        
            if (!empty($response['message'])) {
                $errorMessage .= ' Détail : ' . $response['message'];
            }
        
            return [
                'success' => false,
                'error' => true,
                'message' => $errorMessage,
                'status_code' => $statusCode,
                'data' => $response['response'] ?? null
            ];
        }
    }

    public function initMvola(Transaction $transaction, Order $order): array
    {
        $uuid = Uuid::v4()->toRfc4122();
        $payload = [
            'X-CorrelationID' => $uuid, 
            'partnerMSISDN' => '0380842696', 
            'requestingOrganisationTransactionReference' => 'achat_' . $transaction->getPackage()->getSlug(), 
            'originalTransactionReference' => $order->getOrderNumber(), 
            'partnerName' => 'olona-talents.com', 
            'amount' => (int) $transaction->getAmount(), 
            'description' => 'Achat ' . $transaction->getPackage()->getName(),
            'customerMSISDN' => $transaction->getTelephone(), 
        ];

        $response = $this->mvolaService->paymentsCurl($payload);        
        $statusCode = $response['status_code'] ?? null;

        if (!empty($response) && !empty($response['status']) && !empty($response['serverCorrelationId'])) {
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transaction->setReference($response['serverCorrelationId']);
            $transaction->setToken($uuid);
            $order->setStatus(Order::STATUS_PROCESSING);
            $this->em->persist($transaction);
            $this->em->persist($order);
            $this->em->flush();

        
            return [
                'success' => true,
                'error' => false,
                'message' => 'Transaction en cours de traitement.',
                'status_code' => $statusCode,
                'data' => $response
            ];
        } else {
            $errorMessage = 'Échec de la transaction avec l’API MVola.';
        
            if (!empty($response['message'])) {
                $errorMessage .= ' Détail : ' . $response['message'];
            }
            $transaction->setStatus(Transaction::STATUS_FAILED);
            $transaction->setDetails($errorMessage);
            $order->setStatus(Order::STATUS_FAILED);
            $this->em->persist($transaction);
            $this->em->persist($order);
            $this->em->flush();
        
            return [
                'success' => false,
                'error' => true,
                'message' => $errorMessage,
                'status_code' => $statusCode,
                'data' => $response['response'] ?? null
            ];
        }
    }

    private function formatAirtelNumber($number) {
        $number = preg_replace('/[\s\-\(\)]+/', '', $number);
        if (preg_match('/^(\+261|00261)/', $number)) {
            $number = preg_replace('/^(\+261|00261)/', '', $number);
        }
        if (substr($number, 0, 1) === '0') {
            $number = substr($number, 1);
        }

        return $number;
    }
}