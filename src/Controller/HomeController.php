<?php

namespace App\Controller;

use App\Service\User\UserService;
use App\Repository\SecteurRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Entreprise\JobListingRepository;
use App\Service\Annonce\AnnonceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function __construct(
        private JobListingRepository $jobListingRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private SecteurRepository $secteurRepository,
        private UserService $userService,
        private AnnonceService $annonceService,
    ){
    }
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'sectors' => $this->secteurRepository->findAll(),
            'candidats' => $this->candidateProfileRepository->findTopExperts(),
            'topRanked' => $this->candidateProfileRepository->findTopRanked(),
            'annonces' => $this->jobListingRepository->findAll(),
        ]);
    }

    #[Route('/contact', name: 'app_home_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/legal-mentions', name: 'app_home_legal')]
    public function legal(): Response
    {        
        return $this->render('home/legal.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/privacy-policy', name: 'app_home_privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/terms-and-conditions', name: 'app_home_terms')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/formulaire', name: 'app_home_form')]
    public function form(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $annonceId = $request->request->get('annonce_id');
            $jobId = $request->request->get('job_id');
            $this->annonceService->add($annonceId);
            if (!$this->getUser()) { 
                return $this->redirectToRoute('app_login'); 
            }
            return $this->redirectToRoute('app_dashboard_candidat_annonce_show', ['jobId' => $jobId ]);
        }
    }
}
