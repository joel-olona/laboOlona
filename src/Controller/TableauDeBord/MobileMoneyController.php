<?php

namespace App\Controller\TableauDeBord;

use Symfony\Component\Uid\Uuid;
use App\Entity\Logs\ActivityLog;
use App\Service\User\UserService;
use App\Entity\BusinessModel\Order;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use App\Service\MobileMoney\MvolaService;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\MobileMoney\AirtelMoneyService;
use Symfony\Component\Routing\Annotation\Route;
use App\Manager\BusinessModel\TransactionManager;
use App\Repository\BusinessModel\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/mobile-payment')]

class MobileMoneyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService $userService,
        private AirtelMoneyService $airtelMoneyService,
        private MvolaService $mvolaService,
        private TransactionManager $transactionManager,
        private TransactionRepository $transactionRepository,
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

        $response = json_decode($this->airtelMoneyService->enquiry("551e36cb-2560-4522-bc35-4e7475b1d80b"), true);
        // $response = json_decode($this->airtelMoneyService->kyc("332046888"), true);
        // $response = json_decode($this->airtelMoneyService->payments($payload), true);
        // $response = json_decode($this->airtelMoneyService->disbursements($data), true);
        dd($response);

        return $this->json(
            $response, 
            200, 
            [], 
        );
    }

    #[Route('/airtel-money/callback', name: 'app_mobile_money_airtel_callback', methods: ['POST'])]
    public function airtelCallback(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['transaction'])) {
            return $this->json([
                    'error' => 'Invalid request'
                ], 400, []);
        }

        $transaction = $data['transaction'];
        $transactionId = $transaction['id'];
        $message = $transaction['message'];
        $statusCode = $transaction['status_code'];
        $airtelMoneyId = $transaction['airtel_money_id'];
        $transactionOT = $this->transactionRepository->findOneBy([ 'reference' => $transactionId]);

        if(!$transactionOT instanceof Transaction){
            return $this->json([
                        'error' => 'Transaction not found'
                    ], 404, []);
        }
        
        // Exemple de logique simple
        if ($statusCode === 'TS') {
            // Transaction réussie
            $transactionOT->setStatus(Transaction::STATUS_COMPLETED);
            $transactionOT->setDetails($message);
            $this->transactionManager->save($transactionOT);
            return $this->json([
                        'status' => 'success',
                        'message' => 'Transaction approved',
                    ], 200,[]);

        } elseif ($statusCode === 'TF') {
            // Transaction échouée
            $transactionOT->setStatus(Transaction::STATUS_FAILED);
            $transactionOT->setDetails($message);
            $this->transactionManager->save($transactionOT);
            return $this->json([
                        'status' => 'failed',
                        'message' => 'Transaction failed',
                    ], 403, []);
        }

        return $this->json([
                    'status' => 'success',
                    'message' => 'Callback received and processed',
                ], 200, []);
    }
    
    #[Route('/transaction/airtel-money/status/{id}', name: 'app_transaction_status_airtel_money', methods: ['GET'])]
    public function getStatus(Transaction $transaction): Response
    {
        $response = json_decode($this->airtelMoneyService->enquiry($transaction->getToken()), true);
        // dd($response, $transaction);
        if (isset($response['data']['transaction'])) {
            if($response['data']['transaction']['status'] === 'TS'){
                $transaction->setStatus(Transaction::STATUS_COMPLETED);
            }
            if($response['data']['transaction']['status'] === 'TF'){
                $transaction->setStatus(Transaction::STATUS_FAILED);
            }
            if(isset($response['data']['transaction']['message'])){
                $transaction->setDetails($response['data']['transaction']['message']);
            }
            $this->em->persist($transaction);
            $this->em->flush();
        }
        return $this->json([
            'status' => $transaction->getStatus(),
            'message' => $transaction->getDetails(),
            'token' => $transaction->getToken(),
            'reference' => $transaction->getReference(),
        ]);
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

        $response = json_decode($this->mvolaService->payments($payload), true);

        return $this->json(
            $response, 
            200, 
            [], 
        );
    }
    
    #[Route('/transaction/mvola/status/{id}', name: 'app_transaction_status_mvola', methods: ['GET'])]
    public function getMvolaStatus(Transaction $transaction, TransactionManager $transactionManager, CreditManager $creditManager): Response
    {
        $responseJson = $this->mvolaService->transactionStatus($transaction->getReference());
        $response = json_decode($responseJson, true);
        if (isset($response['status'])) {
            if($response['status'] === 'completed'){
                $transaction->setStatus(Transaction::STATUS_COMPLETED);
                $transaction->getCommand()->setStatus(Order::STATUS_COMPLETED);
                $transactionManager->save($transaction);
                $transactionManager->createInvoice($transaction);
                $creditManager->notifyTransaction($transaction);
                $creditManager->validateTransaction($transaction, $transaction->getTypeTransaction()->getName());
            }
            if($response['status'] === 'pending'){
                $transaction->setStatus(Transaction::STATUS_PENDING);
            }
            if($response['status'] === ''){
                $transaction->setStatus(Transaction::STATUS_PROCESSING);
            }
            if($response['status'] === 'failed'){
                $transaction->setStatus(Transaction::STATUS_FAILED);
            }
            $transaction->setDetails($responseJson);
            $this->em->persist($transaction);
            $this->em->flush();
        }

        return $this->json([
            'status' => $transaction->getStatus(),
            'message' => $transaction->getDetails(),
        ]);
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
