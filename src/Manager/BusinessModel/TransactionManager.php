<?php

namespace App\Manager\BusinessModel;

use App\Entity\User;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\BusinessModel\Credit;
use App\Entity\BusinessModel\Invoice;
use App\Entity\BusinessModel\Order;
use App\Entity\BusinessModel\Package;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BusinessModel\Transaction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class TransactionManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function init(): Transaction
    {
        $transaction = new Transaction();
        $transaction->setTransactionDate(new \DateTime());
        $transaction->setStatus(Transaction::STATUS_PENDING);
        $transaction->setUser($this->security->getUser());

        return $transaction;
    }

    public function save(Transaction $transaction)
    {
        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Transaction $transaction */
        $transaction = $form->getData();
        $package = $transaction->getPackage();
        // $transaction->setAmount($package->getPrice());
        $transaction->setCreditsAdded($package->getCredit());
        $this->save($transaction);

        return $transaction;
    }

    public function validateTransaction(int $transactionId): bool
    {
        $transactionRepository = $this->em->getRepository(Transaction::class);
        $transaction = $transactionRepository->find($transactionId);

        if (!$transaction) {
            return false;
        }

        $user = $transaction->getUser();
        $creditsToAdd = $transaction->getCreditsAdded();

        $credit = $user->getCredit();

        if (!$credit) {
            $credit = $this->init();
            $credit->setUser($user);
        }

        $credit->setTotal($credit->getTotal() + $creditsToAdd);
        $this->em->persist($credit);

        $transaction->setTransactionDetails('Transaction validée');
        $this->em->persist($transaction);
        $this->em->flush();

        return true;
    }

    public function findTransactionSuccessByCommand(Order $order): ?Transaction
    {
        $transaction = $order->getTransaction();
        if(($transaction->getStatus() === Transaction::STATUS_AUTHORIZED)){
            return $transaction;
        }

        return null;
    }

    public function createInvoice(Transaction $transaction) :void
    {
        $order = $transaction->getCommand();
        $invoice = $order->getInvoice();
        if(!$invoice instanceof Invoice){
            $invoice = new Invoice();
        }
        $invoice->setAdress($order->getCustomer()->getAdress());
        $invoice->setPostalCode($order->getCustomer()->getPostalCode());
        $invoice->setCity($order->getCustomer()->getCity());
        $invoice->setName($order->getCustomer()->getNom());
        $invoice->setFirstName($order->getCustomer()->getPrenom());
        $invoice->setCommande($order);

        $this->em->persist($invoice);
        $this->em->flush();
    }
}