<?php

namespace App\Controller\Marketing;

use App\Entity\Marketing\Commission;
use App\Form\Marketing\CommissionType;
use App\Repository\Marketing\CommissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('moderateur/marketing/commission')]
class CommissionController extends AbstractController
{
    #[Route('/', name: 'app_marketing_commission_index', methods: ['GET'])]
    public function index(
        Request $request, 
        CommissionRepository $commissionRepository
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', null);

        return $this->render('marketing/commission/index.html.twig', [
            'commissions' => $commissionRepository->paginateCommissions($page, $status),
            'count' => $commissionRepository->countAll(),
            'countStatus' => $commissionRepository->countStatus($status),
            'statuses' => array_merge(['Tous' => 'ALL' ],Commission::getStatuses()),
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/users', name: 'app_marketing_commission_users', methods: ['GET'])]
    public function users(
        Request $request, 
        CommissionRepository $commissionRepository,
        UserRepository $userRepository,
    ): Response
    {
        $page = $request->query->getInt('page', 1);
        $status = $request->query->get('status', Commission::STATUS_PENDING);
        
        return $this->render('marketing/commission/users.html.twig', [
            'users' => $userRepository->paginateUsersWithTotalCommissions($page, $status),
            'count' => $commissionRepository->countAll(),
            'countStatus' => $commissionRepository->countStatus($status),
            'statuses' => Commission::getStatuses(),
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_marketing_commission_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commission = new Commission();
        $form = $this->createForm(CommissionType::class, $commission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commission);
            $entityManager->flush();

            return $this->redirectToRoute('app_marketing_commission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('marketing/commission/new.html.twig', [
            'commission' => $commission,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_marketing_commission_show', methods: ['GET'])]
    public function show(Commission $commission): Response
    {
        return $this->render('marketing/commission/show.html.twig', [
            'commission' => $commission,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_marketing_commission_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commission $commission, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommissionType::class, $commission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_marketing_commission_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('marketing/commission/edit.html.twig', [
            'commission' => $commission,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_marketing_commission_delete', methods: ['POST'])]
    public function delete(Request $request, Commission $commission, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commission->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commission);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_marketing_commission_index', [], Response::HTTP_SEE_OTHER);
    }
}
