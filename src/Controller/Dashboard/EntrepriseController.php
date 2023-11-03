<?php
namespace App\Controller\Dashboard;

use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\User;
use App\Form\AnnonceType;
use App\Repository\CandidateProfileRepository;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\MettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Search\AnnonceSearchType;
use App\Manager\CandidatManager;
use Symfony\Component\Uid\Uuid;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use Symfony\Component\Form\FormFactoryInterface;
use App\Form\JobListingType;
use App\Entity\Notification;
use Appp\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    ){
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
        $job_listings = $jobListingRepository->findBy(['entreprise' => $entreprise]);
        $applications = $applicationRepository->findBy(['jobListing' => $job_listings]);
        $meetings = $mettingRepository->findBy(['entreprise' => $entreprise]);

        return $this->render('dashboard/entreprise/index.html.twig', [
            'job_listings' => $job_listings,
            'applications' => $applications,
            'meetings' => $meetings,
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_entreprise_annonces')]
    public function annonces(JobListingRepository $jobListingRepository): Response
    {
        // Récupérez les annonces de l'entreprise
        $entreprise = $this->getUser(); // suppose que l'utilisateur connecté est une entreprise
        $job_listings = $jobListingRepository->findBy(['entreprise' => $entreprise]);

        return $this->render('dashboard/entreprise/annonces.html.twig', [
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
            $form = $this->createForm(JobListingType::class, $jobListing);
            $form->handleRequest($request);
        
            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
        
                $this->addFlash('success', 'Annonce modifiée avec succès.');
        
                return $this->redirectToRoute('app_dashboard_entreprise_annonces');
            }
        
            return $this->render('dashboard/entreprise/edit_annonce.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        #[Route('/candidatures', name: 'app_dashboard_entreprise_candidatures')]
        public function candidatures(ApplicationsRepository $applicationRepository): Response
        {
            // Récupérez les candidatures aux annonces de l'entreprise
            $entreprise = $this->getUser(); // suppose que l'utilisateur connecté est une entreprise
            $job_listings = $entreprise->getJobListings();
            $applications = $applicationRepository->findBy(['jobListing' => $job_listings]);
        
            return $this->render('dashboard/entreprise/candidatures.html.twig', [
                'applications' => $applications,
            ]);
        }

    #[Route('/rendez-vous', name: 'app_dashboard_entreprise_rendez_vous')]
    public function rendezVous(MettingRepository $mettingRepository): Response
    {
        // Récupérez les rendez-vous de l'entreprise
        $entreprise = $this->getUser(); // suppose que l'utilisateur connecté est une entreprise
        $rendez_vous = $mettingRepository->findBy(['entreprise' => $entreprise]);
    
        return $this->render('dashboard/entreprise/rendez_vous.html.twig', [
            'rendez_vous' => $rendez_vous,
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

        return $this->render('dashboard/entreprise/recherche_candidats.html.twig', [
            'candidats' => $candidats,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_entreprise_notifications')]
        public function notifications(NotificationRepository $notificationRepository): Response
        {
            $entreprise = $this->getUser(); // suppose que l'utilisateur connecté est une entreprise
            $notifications = $notificationRepository->findBy(['entreprise' => $entreprise]);
        
            return $this->render('dashboard/entreprise/notifications.html.twig', [
                'notifications' => $notifications,
            ]);
        }

        #[Route('/profil', name: 'app_dashboard_entreprise_profil')]
        public function profil(Request $request): Response
        {
            $entreprise = $this->getUser(); // suppose que l'utilisateur connecté est une entreprise
        
            $form = $this->createForm(EntrepriseType::class, $entreprise);
            $form->handleRequest($request);
        
            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
        
                $this->addFlash('success', 'Profil modifié avec succès.');
        
                return $this->redirectToRoute('app_dashboard_entreprise_profil');
            }
        
            return $this->render('dashboard/entreprise/profil.html.twig', [
                'form' => $form->createView(),
            ]);
        }
}