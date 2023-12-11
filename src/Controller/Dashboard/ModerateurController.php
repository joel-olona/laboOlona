<?php

namespace App\Controller\Dashboard;

use App\Form\MettingType;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Entity\Moderateur\Metting;
use App\Manager\ModerateurManager;
use App\Entity\Entreprise\JobListing;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Form\Search\Annonce\ModerateurAnnonceEntrepriseSearchType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Search\Candidat\ModerateurCandidatSearchType;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\Search\Entreprise\ModerateurEntrepriseSearchType;
use App\Manager\CandidatManager;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\TypeContratRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/dashboard/moderateur')]
class ModerateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private CandidatManager $candidatManager,
        private ProfileManager $profileManager,
        private SecteurRepository $secteurRepository,
        private TypeContratRepository $typeContratRepository,
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private MettingRepository $mettingRepository,
        private NotificationRepository $notificationRepository,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    private function checkModerateur()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();
        if (!$moderateur instanceof ModerateurProfile){ 
            return $this->redirectToRoute('app_profile');
        }

        return null;
    }

    #[Route('/', name: 'app_dashboard_moderateur')]
    public function index(): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/index.html.twig', [
            'secteurs' => $this->moderateurManager->findAllOrderDesc($this->secteurRepository),
            'typeContrats' => $this->moderateurManager->findAllOrderDesc($this->typeContratRepository),
            'annonces' => $this->moderateurManager->findAllOrderDesc($this->jobListingRepository),
            'annonces_pending' => $this->jobListingRepository->findBy(['status' => JobListing::STATUS_PENDING], ['id' => 'DESC']),
            'entreprises' => $this->moderateurManager->findAllOrderDesc($this->entrepriseProfileRepository),
            'candidats' => $this->moderateurManager->findAllOrderDesc($this->candidateProfileRepository),
            'candidats_pending' => $this->candidateProfileRepository->findBy(['status' => CandidateProfile::STATUS_PENDING], ['id' => 'DESC']),
            'mettings' => $this->moderateurManager->findAllOrderDesc($this->mettingRepository),
            'notifications' => $this->moderateurManager->findAllOrderDesc($this->notificationRepository),
        ]);
    }

    #[Route('/entreprises', name: 'app_dashboard_moderateur_entreprises')]
    public function entreprises(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllEntreprise();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $secteur = $form->get('secteur')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchEntreprise($nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_entreprises.html.twig', [
                        'entreprises' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/entreprise/index.html.twig', [
            'entreprises' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprise/{id}', name: 'voir_entreprise')]
    public function voirEntreprise(EntrepriseProfile $entreprise): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/entreprise_view.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/supprimer/entreprise/{id}', name: 'supprimer_entreprise', methods: ['POST'])]
    public function supprimerEntreprise(Request $request, EntityManagerInterface $entityManager, EntrepriseProfile $entreprise): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        if ($this->isCsrfTokenValid('delete' . $entreprise->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entreprise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_entreprises');
    }

    #[Route('/entreprises/{id}/annonces', name: 'app_dashboard_moderateur_entreprises_annonces')]
    public function entreprisesAnnonces(Request $request, PaginatorInterface $paginatorInterface, EntrepriseProfile $entreprise, JobListingRepository $jobListingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurAnnonceEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllAnnonceByEntreprise($entreprise);
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $status = $form->get('status')->getData();
            $secteur = $form->get('secteur')->getData();
            $data = $this->moderateurManager->findAllAnnonceEntreprise($entreprise, $nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        $annonces = $jobListingRepository->findBy(['entreprise' => $entreprise]);

        return $this->render('dashboard/moderateur/entreprise/annonces.html.twig', [
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'entreprise' => $entreprise,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprise/{entreprise_id}/annonce/{annonce_id}/status', name: 'change_status_annonce_entreprise', methods: ['POST'])]
    public function changeEntrepriseAnnonceStatus(Request $request, EntityManagerInterface $entityManager, int $entreprise_id, int $annonce_id, JobListingRepository $jobListingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        $entreprise = $entityManager->getRepository(EntrepriseProfile::class)->find($entreprise_id);
        $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
        }

        if ($status && in_array($status, JobListing::getArrayStatuses())) {
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
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

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

    #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
    public function candidats(Request $request, PaginatorInterface $paginatorInterface, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche candidat */
        $form = $this->createForm(ModerateurCandidatSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllCandidat();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $titre = $form->get('titre')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchCandidat($nom, $titre, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/candidat/_candidats.html.twig', [
                        'candidats' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/candidat/index.html.twig', [
            'candidats' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/candidat/{uid}/status', name: 'change_status_candidat', methods: ['POST'])]
    public function changeCandidatStatus(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidateProfile): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, ['PENDING', 'BANNISHED', 'VALID', 'FEATURED', 'RESERVED'])) {
            $candidateProfile->setStatus($status);
            $entityManager->flush();
            if($status === CandidateProfile::STATUS_VALID){
                /** On envoi un mail */
                $this->mailerService->send(
                    $candidateProfile->getCandidat()->getEmail(),
                    "Statut de votre profil sur Olona Talents",
                    "validate_profile.html.twig",
                    [
                        'user' => $candidateProfile->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidats');
    }

    #[Route('/candidat/{uid}/certification', name: 'change_status_certification_candidat', methods: ['POST'])]
    public function changeCertificationCandidatStatus(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidateProfile): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, ['OUI', 'NON'])) {
            $candidateProfile->setStatus($status);
            $entityManager->flush();
            if($status === CandidateProfile::STATUS_VALID){
                /** On envoi un mail */
                $this->mailerService->send(
                    $candidateProfile->getCandidat()->getEmail(),
                    "Statut de votre profil sur Olona Talents",
                    "validate_profile.html.twig",
                    [
                        'user' => $candidateProfile->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidats');
    }


    #[Route('/candidats/{id}', name: 'app_dashboard_moderateur_candidat_view')]
    public function viewCandidat(CandidateProfile $candidat): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/candidat_view.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/candidats/{id}/applications', name: 'app_dashboard_moderateur_candidat_applications')]
    public function candidatApplications(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id]);

        return $this->render('dashboard/moderateur/candidat_applications.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/status/application/{id}', name: 'change_status_application', methods: ['POST'])]
    public function changeApplicationStatus(Request $request, EntityManagerInterface $entityManager, Applications $application): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, Applications::getArrayStatuses())) {
            $application->setStatus($status);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');

            /** Envoi mail candidat */
            $this->mailerService->send(
                $application->getCandidat()->getCandidat()->getEmail(),
                "Statut de votre candidature sur Olona Talents",
                "candidat/status_candidature.html.twig",
                [
                    'user' => $application->getCandidat()->getCandidat(),
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_annonces', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Envoi mail entreprise */
            $this->mailerService->send(
                $application->getAnnonce()->getEntreprise()->getEntreprise()->getEmail(),
                "Une candidature a été déposée sur votre annonce sur Olona Talents",
                "entreprise/status_candidature.html.twig",
                [
                    'user' => $application->getAnnonce()->getEntreprise()->getEntreprise(),
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_candidatures', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Envoi mail moderateurs */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Une nouvelle candidature a été déposée pour une annonce sur Olona Talents",
                "moderateur/status_candidature.html.twig",
                [
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidat_applications', ['id' => $application->getCandidat()->getId()]);
    }

    #[Route('/candidats/{id}/applications/en-attente', name: 'app_dashboard_moderateur_candidat_applications_en_attente')]
    public function candidatApplicationsEnAttente(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'PENDING']);

        return $this->render('dashboard/moderateur/candidat_applications_en_attente.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/applications/acceptees', name: 'app_dashboard_moderateur_candidat_applications_acceptees')]
    public function candidatApplicationsAcceptees(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'ACCEPTED']);

        return $this->render('dashboard/moderateur/candidat_applications_acceptees.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/applications/refusees', name: 'app_dashboard_moderateur_candidat_applications_refusees')]
    public function candidatApplicationsRefusees(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'REFUSED']);

        return $this->render('dashboard/moderateur/candidat_applications_refusees.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/competences', name: 'app_dashboard_moderateur_candidat_competences')]
    public function candidatCompetences(int $id, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $candidat = $candidateProfileRepository->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat introuvable');
        }

        return $this->render('dashboard/moderateur/candidat_competences.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/candidats/{id}/experiences', name: 'app_dashboard_moderateur_candidat_experiences')]
    public function candidatExperiences(int $id, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $candidat = $candidateProfileRepository->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat introuvable');
        }

        $experiences = $candidat->getExperiences();

        return $this->render('dashboard/moderateur/candidat_experiences.html.twig', [
            'candidat' => $candidat,
            'experiences' => $experiences,
        ]);
    }

    #[Route('/mettings', name: 'app_dashboard_moderateur_mettings')]
    public function mettings(MettingRepository $mettingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $mettings = $mettingRepository->findAll();
        return $this->render('dashboard/moderateur/mettings.html.twig', compact('mettings'));
    }

    #[Route('/metting/show/{id}', name: 'app_dashboard_moderateur_metting_show', methods: ['GET'])]
    public function show(Metting $metting): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/mettings_show.html.twig', compact('metting'));
    }

    #[Route('/metting/new', name: 'app_dashboard_moderateur_metting_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $metting = new Metting();
        $form = $this->createForm(MettingType::class, $metting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($metting);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_moderateur_mettings');
        }

        return $this->render('dashboard/moderateur/mettings_new.html.twig', [
            'metting' => $metting,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/metting/{id}/edit', name: 'app_dashboard_moderateur_metting_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Metting $metting, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $form = $this->createForm(MettingType::class, $metting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_moderateur_mettings');
        }

        return $this->renderForm('dashboard/moderateur/mettings_edit.html.twig', [
            'metting' => $metting,
            'form' => $form,
        ]);
    }

    #[Route('/metting/{id}', name: 'app_dashboard_moderateur_metting_delete', methods: ['POST'])]
    public function delete(Request $request, Metting $metting, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        if ($this->isCsrfTokenValid('delete' . $metting->getId(), $request->request->get('_token'))) {
            $entityManager->remove($metting);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_mettings');
    }


    #[Route('/notifications', name: 'app_dashboard_moderateur_notifications')]
    public function notifications(Request $request, NotificationRepository $notificationRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }
        
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'sectors' => $notificationRepository->findAll(),
        ]);
    }
}
