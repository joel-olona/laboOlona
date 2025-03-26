<?php 

namespace App\Manager\MobileMoney;

use App\Entity\BusinessModel\Transaction;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use App\Entity\User;
use App\Entity\Finance\Devise;
use App\Entity\Logs\ActivityLog;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Service\MobileMoney\AirtelMoneyService;
use Doctrine\ORM\EntityManagerInterface;

class MobileMoneyManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private AirtelMoneyService $airtelMoneyService
    )
    {}

    public function initAirtelMoney(Transaction $transaction, Order $order): array
    {
        $payload = [
            "reference" => 'Achat ' . $transaction->getPackage()->getName(),
            "subscriber" => [
                "country" => "MG",
                "currency" => "MGA",
                "msisdn" => $this->formatAirtelNumber($transaction->getTelephone()),
            ],
            "transaction" => [
                "amount" => (int) $transaction->getPackage()->getPrice(),
                "country" => "MG",
                "currency" => "MGA",
                "id" => $order->getOrderNumber()
            ]
        ];

        $response = json_decode($this->airtelMoneyService->payments($payload), true);

        if (!empty($response) && !empty($response['status']) && !empty($response['data'])) {
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transaction->setReference($response['data']['transaction']['id']);
            $this->em->persist($transaction);
            $this->em->flush();
            $order->setStatus(Order::STATUS_PROCESSING);
            $this->em->persist($order);
            $this->em->flush();

            return $response;
        } else {
            return [];
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