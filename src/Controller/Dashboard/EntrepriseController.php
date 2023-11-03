<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Service\User\UserService;
use App\Form\Entreprise\AnnonceType;
use App\Form\Profile\EntrepriseType;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
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
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    private function checkEntreprise()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        if (!$entreprise instanceof EntrepriseProfile) return $this->redirectToRoute('app_profile');
    }

    #[Route('/', name: 'app_dashboard_entreprise')]
    public function index(JobListingRepository $jobListingRepository, ApplicationsRepository $applicationRepository, MettingRepository $mettingRepository): Response
    {
        $this->checkEntreprise();
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
    public function annonces(JobListingRepository $jobListingRepository): Response
    {
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();
        $job_listings = $jobListingRepository->findBy(['entreprise' => $entreprise]);

        return $this->render('dashboard/entreprise/annonce/annonces.html.twig', [
            'job_listings' => $job_listings,
        ]);
    }

    #[Route('/annonce/new', name: 'app_dashboard_entreprise_new_annonce')]
    public function newAnnonce(Request $request): Response
    {
        $this->checkEntreprise();
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
        $this->checkEntreprise();

        $form = $this->createForm(AnnonceType::class, $jobListing);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($jobListing);
            $this->em->flush();
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
        $this->checkEntreprise();
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
        $this->checkEntreprise();
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
        $this->checkEntreprise();
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
        $this->checkEntreprise();
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

    #[Route('/notifications', name: 'app_dashboard_entreprise_notifications')]
    public function notifications(): Response
    {
        $this->checkEntreprise();
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
        $this->checkEntreprise();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $entreprise = $user->getEntrepriseProfile();

        $form = $this->createForm(EntrepriseType::class, $entreprise);
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
