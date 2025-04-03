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
        $amount = $transaction->getAmount();
        $payload = [
            "reference" => 'Achat ' . $transaction->getPackage()->getName(),
            "subscriber" => [
                "country" => "MG",
                "currency" => "MGA",
                "msisdn" => $this->formatAirtelNumber($transaction->getTelephone()),
            ],
            "transaction" => [
                "amount" => $amount,
                "country" => "MG",
                "currency" => "MGA",
                "id" => $uuid
            ]
        ];

        $response = $this->airtelMoneyService->payments($payload);
        if (!empty($response) && isset($response['status']) && isset($response['data'])) {
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transaction->setReference($response['data']['transaction']['id']);
            $transaction->setToken($uuid);
            $transaction->setAmount($amount);
            $this->em->persist($transaction);
            $this->em->flush();
            $order->setStatus(Order::STATUS_PROCESSING);
            $this->em->persist($order);
            $this->em->flush();

            return $response;
        } else {
            return $response;
        }
    }

    public function initMvola(Transaction $transaction, Order $order): array
    {
        $uuid = Uuid::v4()->toRfc4122();
        $payload = [
            'X-CorrelationID' => $uuid, 
            'partnerMSISDN' => '0343500003', 
            'requestingOrganisationTransactionReference' => 'achat_' . $transaction->getPackage()->getSlug(), 
            'originalTransactionReference' => $order->getOrderNumber(), 
            'partnerName' => 'olona-talents.com', 
            'amount' => $transaction->getAmount(), 
            'description' => 'Achat ' . $transaction->getPackage()->getName(),
            'customerMSISDN' => $transaction->getTelephone(), 
        ];

        $response = json_decode($this->mvolaService->payments($payload), true);

        if (!empty($response) && !empty($response['status']) && !empty($response['serverCorrelationId'])) {
            $transaction->setStatus(Transaction::STATUS_PROCESSING);
            $transaction->setReference($response['serverCorrelationId']);
            $transaction->setToken($uuid);
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