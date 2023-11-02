<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/dashboard/candidat')]
class CandidatController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_candidat')]
    public function index(): Response
    {
        return $this->render('dashboard/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_candidat_annonce')]
    public function annonces(): Response
    {
        return $this->render('dashboard/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }
    
}
