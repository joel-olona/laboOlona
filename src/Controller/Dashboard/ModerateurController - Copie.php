<?php

namespace App\Controller\Dashboard;

use App\Manager\ProfileManager;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Moderateur\SecteurType;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use App\Repository\Entreprise\JobListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\MettingRepository;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/dashboard/moderateur')]
class ModerateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private ProfileManager $profileManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ){
    }
    
    #[Route('/', name: 'app_dashboard_moderateur')]
    public function index(): Response
    {
        return $this->render('dashboard/moderateur/index.html.twig', [
            'controller_name' => 'ModerateurController',
        ]);
    }

    #[Route('/secteurs', name: 'app_dashboard_moderateur_sector')]
    public function sectors(Request $request, SecteurRepository $secteurRepository): Response
    {
        /* return $this->render('dashboard/moderateur/sectors.html.twig', [
            'sectors' => $secteurRepository->findAll(),
        ]); */
        return $this->render('dashboard/moderateur/sectors.html.twig', [
            'sectors' => $secteurRepository->findAll(),
        ]);
    }
    
    #[Route('/ajax/remove/{id}/sector', name: 'ajax_remove_sector', methods: ['DELETE'])]
    public function ajaxRemoveSector(int $id, SecteurRepository $secteurRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $secteur = $secteurRepository->find($id);
        if (!$secteur) {
            return $this->json(['success' => false, 'message' => 'Secteur non trouvé'], 404);
        }
    
        $entityManager->remove($secteur);
        $entityManager->flush();
    
        return $this->json(['success' => true, 'message' => 'Secteur supprimé']);
    }
    // #[Route('/secteurs/delete/{id}', name: 'app_dashboard_moderateur_delete_sector', methods: ['DELETE'])]
    // public function deleteSector(int $id, SecteurRepository $secteurRepository, EntityManagerInterface $entityManager): JsonResponse
    // {  
    //     $secteur = $secteurRepository->find($id);
    //     if (!$secteur) {
    //         return $this->json(['success' => false, 'message' => 'Secteur non trouvé'], 404);
    //     }

    //     $entityManager->remove($secteur);
    //     $entityManager->flush();

    //     return $this->json(['success' => true, 'message' => 'Secteur supprimé']);
    //     }

    #[Route('/secteur/new', name: 'app_dashboard_moderateur_new_sector')]
    public function sector(Request $request): Response
    {
        $secteur = $this->moderateurManager->initSector();
        $form = $this->createForm(SecteurType::class, $secteur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $secteur = $this->moderateurManager->saveSectorForm($form);

            return $this->redirectToRoute('app_dashboard_moderateur_sector', []);
        }

        return $this->render('dashboard/moderateur/sector.html.twig', [
            'form' => $form->createView(),
        ]);
    }

        #[Route('/annonces', name: 'app_dashboard_moderateur_annonces')]
        public function annonces(Request $request, JobListingRepository $jobListingRepository): Response
        {
            return $this->render('dashboard/moderateur/annonces.html.twig', [
                'annonces' => $jobListingRepository->findAll(),
            ]);
        }

        #[Route('/ajax/status/annonce/{id}', name: 'ajax_change_status_annonce', methods: ['POST'])]
        public function changeAnnonceStatus(EntityManagerInterface $entityManager, Request $request, Annonce $annonce): Response
        {
            try {
                $newStatus = $request->request->get('status');
                $annonce->setStatus($newStatus);
                $entityManager->flush();
        
                return $this->json(['success' => true]);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'error' => 'Erreur lors de la mise à jour du statut']);
            }
        }
        
        #[Route('/ajax/delete/annonce/{id}', name: 'ajax_delete_annonce', methods: ['DELETE'])]
        public function deleteAnnonce(int $id, JobListingRepository $jobListingRepository, EntityManagerInterface $entityManager): JsonResponse
        {
            $annonce = $jobListingRepository->find($id);
            if (!$annonce) {
                return $this->json(['success' => false, 'message' => 'Annonce non trouvée'], 404);
            }
        
            try {
                $entityManager->remove($annonce);
                $entityManager->flush();
        
                return $this->json(['success' => true, 'message' => 'Annonce supprimée']);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => 'Erreur lors de la suppression'], 500);
            }
        }
        
        #[Route('/ajax/details/annonce/{id}', name: 'ajax_details_annonce', methods: ['GET'])]
            public function detailsAnnonce(Annonce $annonce): Response
            {
                $data = [
                    'id' => $annonce->getId(),
                    'titre' => $annonce->getTitre(),
                    'description' => $annonce->getDescription(),
                    // Ajoutez ici d'autres champs que vous souhaitez afficher
                ];
            
                return $this->json($data);
            }

        #[Route('/entreprises', name: 'app_dashboard_moderateur_entreprises')]
        public function entreprises(Request $request, EntrepriseProfileRepository $entrepriseProfileRepository): Response
        {
            return $this->render('dashboard/moderateur/entreprises.html.twig', [
                'sectors' => $entrepriseProfileRepository->findAll(),
            ]);
        }

    #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
    public function candidats(Request $request, CandidateProfileRepository $candidateProfileRepository): Response
    {
        return $this->render('dashboard/moderateur/candidats.html.twig', [
            'sectors' => $candidateProfileRepository->findAll(),
        ]);
    }

    #[Route('/mettings', name: 'app_dashboard_moderateur_mettings')]
    public function mettings(Request $request, MettingRepository $mettingRepository): Response
    {
        return $this->render('dashboard/moderateur/mettings.html.twig', [
            'sectors' => $mettingRepository->findAll(),
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_moderateur_notifications')]
    public function notifications(Request $request, NotificationRepository $notificationRepository): Response
    {
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'sectors' => $notificationRepository->findAll(),
        ]);
    }
}
