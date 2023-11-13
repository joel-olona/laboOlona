<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Manager\EntrepriseManager;
use App\Manager\ModerateurManager;
use App\Form\Entreprise\AnnonceType;
use App\Form\Profile\EntrepriseType;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Form\Profile\EditEntrepriseType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Search\EntrepriseAnnonceSearchType;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\TypeContratRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/entreprise')]
class EntrepriseController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private CandidatManager $candidatManager,
        private ModerateurManager $moderateurManager,
        private EntrepriseManager $entrepriseManager,
        private RequestStack $requestStack,
        private ApplicationsRepository $applicationRepository,
        private TypeContratRepository $typeContratRepository,
        private MettingRepository $mettingRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    private function checkEntreprise(): ?Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        if(!$entreprise instanceof EntrepriseProfile){
           return $this->redirectToRoute('app_profile');
        }

        return null;
    }

    #[Route('/', name: 'app_dashboard_entreprise')]
    public function index(JobListingRepository $jobListingRepository, ApplicationsRepository $applicationRepository, MettingRepository $mettingRepository): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->redirectToRoute('app_dashboard_entreprise_annonces');

        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/index.html.twig', [
            'job_listings' => $jobListingRepository->findBy(['entreprise' => $entreprise]),
            'applications' => $applicationRepository->findBy(['jobListing' => $entreprise]),
            'meetings' => $mettingRepository->findBy(['entreprise' => $entreprise]),
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_entreprise_annonces')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface, JobListingRepository $jobListingRepository): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $typesContrat = $this->typeContratRepository->findAll();
        $job_listings = $jobListingRepository->findBy(['entreprise' => $entreprise]);
        $form = $this->createForm(EntrepriseAnnonceSearchType::class, null, [
            'types_contrat' => $typesContrat,
        ]);
        $form->handleRequest($request);
        $data = $this->entrepriseManager->findAllAnnonces();
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $status = $form->get('status')->getData();
            $typeContratObjet = $form->get('typeContrat')->getData();
            $typeContrat = $typeContratObjet ? $typeContratObjet->getNom() : null; 
            $salaire = $form->get('salaire')->getData();
            $data = $this->entrepriseManager->findAllAnnonces($titre, $status, $typeContrat, $salaire);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/entreprise/annonce/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'job_listings' => $jobListingRepository->findBy(['entreprise' => $entreprise]),
                        'applications' => $this->applicationRepository->findBy(['jobListing' => $entreprise]),
                        'meetings' => $this->mettingRepository->findBy(['entreprise' => $entreprise]),
                        'result' => $data
                    ])
                ]);
            }
        }


        return $this->render('dashboard/entreprise/annonce/annonces.html.twig', [
            'job_listings' => $jobListingRepository->findBy(['entreprise' => $entreprise]),
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'applications' => $this->applicationRepository->findBy(['jobListing' => $entreprise]),
            'meetings' => $this->mettingRepository->findBy(['entreprise' => $entreprise]),
            'form' => $form->createView()
        ]);
    }

    #[Route('/annonce/new', name: 'app_dashboard_entreprise_new_annonce')]
    public function newAnnonce(Request $request): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $jobListing = new JobListing();
        $jobListing->setEntreprise($entreprise); // suppose que l'utilisateur connecté est une entreprise
        $jobListing->setDateCreation(new \DateTime());
        $jobListing->setJobId(new Uuid(Uuid::v1()));
        $jobListing->setStatus(JobListing::STATUS_PENDING);

        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($jobListing);
            $this->em->flush();
            /** Envoi email moderateur */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Nouvelle annonce sur Olona Talents",
                "moderateur/notification_annonce.html.twig",
                [
                    'user' => $entreprise->getEntreprise(),
                    'objet' => "ajoutée",
                    'details_annonce' => $jobListing,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_annonce_view', ['id' => $jobListing->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            /** Envoi email entreprise apres avoir publier une annonce */
            $this->mailerService->send(
                $entreprise->getEntreprise()->getEmail(),
                "Votre soumission d'annonce sur Olona Talents a été prise en compte",
                "entreprise/notification_annonce.html.twig",
                [
                    'user' => $entreprise,
                    'details_annonce' => $jobListing,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_annonce_view', ['id' => $jobListing->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Annonce créée avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_annonces');
        }

        return $this->render('dashboard/entreprise/annonce/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}/edit', name: 'app_dashboard_entreprise_edit_annonce')]
    public function editAnnonce(Request $request, JobListing $jobListing): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }

        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobListing = $form->getData();
            $jobListing->setStatus(JobListing::STATUS_PENDING);
            $this->em->persist($jobListing);
            $this->em->flush();
            /** Envoi email moderateur */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Mis à jour d'une annonce sur Olona Talents",
                "moderateur/notification_annonce.html.twig",
                [
                    'user' => $jobListing->getEntreprise()->getEntreprise(),
                    'objet' => "mise à jour",
                    'details_annonce' => $jobListing,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_annonce_view', ['id' => $jobListing->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Annonce modifiée avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_annonces');
        }

        return $this->render('dashboard/entreprise/annonce/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}/delete', name: 'app_dashboard_entreprise_delete_annonce')]
    public function deleteAnnonce(Request $request, JobListing $jobListing): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        if ($this->isCsrfTokenValid('delete'.$jobListing->getId(), $request->request->get('_token'))) {
            $this->em->remove($jobListing);
            $this->em->flush();
            $this->addFlash('success', 'Annonce supprimée.');
        }

        return $this->redirectToRoute('app_dashboard_entreprise_annonces');
    }

    #[Route('/annonce/{id}/view', name: 'app_dashboard_entreprise_view_annonce')]
    public function viewAnnonce(Request $request, JobListing $jobListing): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->render('dashboard/entreprise/annonce/view.html.twig', [
            'annonce' => $jobListing,
            'entreprise' => $user->getEntrepriseProfile(),
        ]);
    }

    #[Route('/candidatures', name: 'app_dashboard_entreprise_candidatures')]
    public function candidatures(): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $annonces = $entreprise->getJobListings();
        $applications = [];
        foreach($annonces as $annonce){
            $annonceApplications = $annonce->getApplications();
        
            if ($annonceApplications instanceof \Doctrine\ORM\PersistentCollection) {
                // Convertir la collection en un tableau
                $annonceApplications = $annonceApplications->toArray();
            }
        
            $applications = array_merge($applications, $annonceApplications);
        }

        return $this->render('dashboard/entreprise/candidatures/index.html.twig', [
            'annonces' => $annonces,
            'applications' => $applications,
        ]);
    }

    #[Route('/rendez-vous', name: 'app_dashboard_entreprise_rendez_vous')]
    public function rendezVous(): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/metting/index.html.twig', [
            'rendez_vous' => $entreprise->getMettings(),
        ]);
    }

    #[Route('/recherche-candidats', name: 'app_dashboard_entreprise_recherche_candidats')]
    public function rechercheCandidats(Request $request, CandidateProfileRepository $candidatRepository): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        $searchTerm = $request->query->get('q');

        if ($searchTerm) {
            $candidats = $candidatRepository->search($searchTerm);
        } else {
            $candidats = [];
        }

        return $this->render('dashboard/entreprise/candidat/index.html.twig', [
            'candidats' => $candidatRepository->findAll(),
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/details-candidat/{id}', name: 'app_dashboard_entreprise_details_candidat')]
    public function detailsCandidat(Request $request, CandidateProfile $candidateProfile): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/candidat/show.html.twig', [
            'candidat' => $candidateProfile,
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_entreprise_notifications')]
    public function notifications(): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        return $this->render('dashboard/entreprise/notification/index.html.twig', [
            'notifications' => $user->getRecus(),
        ]);
    }

    #[Route('/profil', name: 'app_dashboard_entreprise_profil')]
    public function profil(Request $request): Response
    {
        $redirection = $this->checkEntreprise();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        $form = $this->createForm(EditEntrepriseType::class, $entreprise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($entreprise);
            $this->em->flush();
            $this->addFlash('success', 'Profil modifié avec succès.');

            return $this->redirectToRoute('app_dashboard_entreprise_profil');
        }

        return $this->render('dashboard/entreprise/profile/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
