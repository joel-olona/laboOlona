<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/entreprise')]
class EntrepriseController extends AbstractController
{
    #[Route('/', name: 'app_dashboard_entreprise')]
    public function index(): Response
    {
        return $this->render('dashboard/entreprise/index.html.twig', [
            'controller_name' => 'EntrepriseController',
        ]);
    }

    #[Route('/new/annonce', name: 'app_dashboard_entreprise_new_annonce')]
    public function new(): Response
    {
        return $this->render('dashboard/entreprise/index.html.twig', [
            'controller_name' => 'EntrepriseController',
        ]);
    }
    
    #[Route('/annonces', name: 'app_dashboard_entreprise_annonces')]
    public function annonces(): Response
    {
        return $this->render('dashboard/entreprise/index.html.twig', [
            'controller_name' => 'EntrepriseController',
        ]);
    }
    
    #[Route('/candidats', name: 'app_dashboard_entreprise_candidats')]
    public function candidats(): Response
    {
        return $this->render('dashboard/entreprise/index.html.twig', [
            'controller_name' => 'EntrepriseController',
        ]);
    }
    #[Route('/compte', name: 'app_dashboard_entreprise_compte')]
    public function compte(): Response
    {
        return $this->render('dashboard/entreprise/index.html.twig', [
            'controller_name' => 'EntrepriseController',
        ]);
    }
}
