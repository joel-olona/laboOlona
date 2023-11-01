<?php

namespace App\Controller\Dashboard;

use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Form\JobListingType;
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
use App\Entity\CandidateProfile;
use App\Entity\Candidate\Applications;
use App\Entity\Candidate\Competences;
use App\Entity\Candidate\Experiences;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Candidate\CompetencesRepository;
use App\Repository\Candidate\ExperiencesRepository;

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
public function annonces(JobListingRepository $jobListingRepository): Response
{
    return $this->render('dashboard/moderateur/annonces.html.twig', [
        'annonces' => $jobListingRepository->findAll(),
    ]);
}

#[Route('/annonce/{id}', name: 'view_annonce', methods: ['GET'])]
public function viewAnnonce(JobListing $annonce): Response
{
    return $this->render('dashboard/moderateur/view.html.twig', [
        'annonce' => $annonce,
    ]);
}

#[Route('/status/annonce/{id}', name: 'change_status_annonce', methods: ['POST'])]
public function changeAnnonceStatus(Request $request, EntityManagerInterface $entityManager, JobListing $annonce): Response
{
    $status = $request->request->get('status');
    if ($status && in_array($status, ['OPEN', 'CLOSED', 'FILLED'])) {
        $annonce->setStatus($status);
        $entityManager->flush();
        $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
    } else {
        $this->addFlash('error', 'Statut invalide.');
    }

    return $this->redirectToRoute('app_dashboard_moderateur_annonces');
}



#[Route('/delete/annonce/{id}', name: 'delete_annonce', methods: ['POST'])]
public function deleteAnnonce(JobListing $annonce, EntityManagerInterface $entityManager): Response
{
    $entityManager->remove($annonce);
    $entityManager->flush();
    $this->addFlash('success', 'Annonce supprimée avec succès.');

    return $this->redirectToRoute('app_dashboard_moderateur_annonces');
}

#[Route('/details/annonce/{id}', name: 'details_annonce', methods: ['GET'])]
public function detailsAnnonce(JobListing $annonce): JsonResponse
{
    $annonceDetails = [
        'titre' => $annonce->getTitre(),
        'description' => $annonce->getDescription(),
        'dateCreation' => $annonce->getDateCreation()?->format('Y-m-d H:i:s'),
        'dateExpiration' => $annonce->getDateExpiration()?->format('Y-m-d H:i:s'),
        'status' => $annonce->getStatus(),
        'salaire' => $annonce->getSalaire(),
        'lieu' => $annonce->getLieu(),
        'typeContrat' => $annonce->getTypeContrat(),
        // // Si 'entreprise' et 'entreprise.nom' sont des entités ou des objets, vous devez vous assurer qu'ils sont correctement initialisés et qu'ils ont une méthode toString() ou similaire.
        // 'entreprise' => (string)$annonce->getEntreprise()?->getEntreprise()?->getNom(),
    ];

    return $this->json($annonceDetails);
}


        #[Route('/entreprises', name: 'app_dashboard_moderateur_entreprises')]
        public function entreprises(EntrepriseProfileRepository $entrepriseProfileRepository): Response
        {
            $entreprises = $entrepriseProfileRepository->findAll();
            return $this->render('dashboard/moderateur/entreprises.html.twig', [
                'entreprises' => $entreprises,
            ]);
        }

        #[Route('/entreprise/{id}', name: 'voir_entreprise')]
        public function voirEntreprise(EntrepriseProfile $entreprise): Response
        {
            return $this->render('dashboard/moderateur/entreprise_view.html.twig', [
                'entreprise' => $entreprise,
            ]);
        }

        #[Route('/supprimer/entreprise/{id}', name: 'supprimer_entreprise', methods: ['POST'])]
        public function supprimerEntreprise(Request $request, EntityManagerInterface $entityManager, EntrepriseProfile $entreprise): Response
        {
            if ($this->isCsrfTokenValid('delete'.$entreprise->getId(), $request->request->get('_token'))) {
                $entityManager->remove($entreprise);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_dashboard_moderateur_entreprises');
        }

        #[Route('/entreprises/{id}/annonces', name: 'app_dashboard_moderateur_entreprises_annonces')]
            public function entreprisesAnnonces(EntrepriseProfile $entreprise, JobListingRepository $jobListingRepository): Response
            {
                $annonces = $jobListingRepository->findBy(['entreprise' => $entreprise]);

                return $this->render('dashboard/moderateur/entreprises_annonces.html.twig', [
                    'entreprise' => $entreprise,
                    'annonces' => $annonces,
                ]);
            }

            #[Route('/entreprise/{entreprise_id}/annonce/{annonce_id}/status', name: 'change_status_annonce_entreprise', methods: ['POST'])]
            public function changeEntrepriseAnnonceStatus(Request $request, EntityManagerInterface $entityManager, int $entreprise_id, int $annonce_id, JobListingRepository $jobListingRepository): Response
            {
                $status = $request->request->get('status');
                $entreprise = $entityManager->getRepository(EntrepriseProfile::class)->find($entreprise_id);
                $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);
        
                if (!$annonce) {
                    $this->addFlash('error', 'Annonce introuvable.');
                    return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
                }
        
                if ($status && in_array($status, ['OPEN', 'CLOSED', 'FILLED'])) {
                    $annonce->setStatus($status);
                    $entityManager->flush();
                    $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
                } else {
                    $this->addFlash('error', 'Statut invalide.');
                }
        
                return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
            }

            #[Route('/entreprises/{entreprise_id}/annonces/{annonce_id}/view', name: 'app_dashboard_moderateur_entreprises_annonces_view')]
            public function entreprisesAnnoncesView(int $entreprise_id, int $annonce_id, EntrepriseProfileRepository $entrepriseProfileRepository, JobListingRepository $jobListingRepository): Response
            {
                $entreprise = $entrepriseProfileRepository->find($entreprise_id);
                $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);
            
                if (!$entreprise || !$annonce) {
                    throw $this->createNotFoundException('L\'entreprise ou l\'annonce n\'a pas été trouvée');
                }
            
                return $this->render('dashboard/moderateur/entreprises_annonces_view.html.twig', [
                    'entreprise' => $entreprise,
                    'annonce' => $annonce,
                ]);
            }

    // #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
    // public function candidats(Request $request, CandidateProfileRepository $candidateProfileRepository): Response
    // {
    //     return $this->render('dashboard/moderateur/candidats.html.twig', [
    //         'sectors' => $candidateProfileRepository->findAll(),
    //     ]);
    // }

    #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
        public function candidats(CandidateProfileRepository $candidateProfileRepository): Response
        {
            $candidats = $candidateProfileRepository->findAll();
            dd($candidats);
            return $this->render('dashboard/moderateur/candidats.html.twig', [
                'candidats' => $candidats,
            ]);
        }

        #[Route('/candidats/{id}/applications', name: 'app_dashboard_moderateur_candidat_applications')]
        public function candidatApplications(int $id, ApplicationsRepository $applicationsRepository): Response
        {
            $applications = $applicationsRepository->findBy(['candidat' => $id]);

            return $this->render('dashboard/moderateur/candidat_applications.html.twig', [
                'applications' => $applications,
            ]);
        }

        #[Route('/candidats/{id}/competences', name: 'app_dashboard_moderateur_candidat_competences')]
        public function candidatCompetences(int $id, CandidateProfileRepository $candidateProfileRepository, CompetencesRepository $competencesRepository): Response
        {
            $candidat = $candidateProfileRepository->find($id);
            if (!$candidat) {
                throw $this->createNotFoundException('Candidat introuvable');
            }

            $competences = $competencesRepository->findByProfil($candidat);

            return $this->render('dashboard/moderateur/candidat_competences.html.twig', [
                'competences' => $competences,
            ]);
        }

        #[Route('/candidats/{id}/experiences', name: 'app_dashboard_moderateur_candidat_experiences')]
        public function candidatExperiences(int $id, ExperiencesRepository $experiencesRepository): Response
        {
            $experiences = $experiencesRepository->findBy(['profil' => $id]);

            return $this->render('dashboard/moderateur/candidat_experiences.html.twig', [
                'experiences' => $experiences,
            ]);
        }

            #[Route('/candidats/{id}/competences', name: 'view_candidat_competences')]
            public function viewCandidatCompetences(CandidateProfile $candidat, CompetencesRepository $competencesRepository): Response {
                $competences = $competencesRepository->findBy(['profil' => $candidat]);
                return $this->render('dashboard/moderateur/view_candidat_competences.html.twig', [
                    'candidat' => $candidat,
                    'competences' => $competences,
                ]);
            }

        #[Route('/candidats/{id}/experiences', name: 'view_candidat_experiences')]
            public function viewCandidatExperiences(CandidateProfile $candidat): Response {
                $experiences = $candidat->getExperiences();
                return $this->render('dashboard/moderateur/view_candidat_experiences.html.twig', [
                    'candidat' => $candidat,
                    'experiences' => $experiences,
                ]);
            }

            #[Route('/candidats/{id}/applications', name: 'view_candidat_applications')]
            public function viewCandidatApplications(CandidateProfile $candidat): Response {
                $applications = $candidat->getApplications();
                return $this->render('dashboard/moderateur/view_candidat_applications.html.twig', [
                    'candidat' => $candidat,
                    'applications' => $applications,
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
