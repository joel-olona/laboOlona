<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Entity\User;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\Vues\AnnonceVues;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\TypeContrat;
use App\Entity\Notification;
use App\Form\Search\AnnonceSearchType;
use App\Form\Candidat\ApplicationsType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Profile\Candidat\StepOneType;
use App\Form\Profile\Candidat\StepTwoType;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Profile\Candidat\StepThreeType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Search\Annonce\CandidatAnnonceSearchType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\TypeContratRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Profile\Candidat\Edit\StepOneType as EditStepOneType;
use App\Form\Profile\Candidat\Edit\StepTwoType as EditStepTwoType;
use App\Form\Profile\Candidat\Edit\StepThreeType as EditStepThreeType;
use App\Manager\ModerateurManager;
use App\Manager\NotificationManager;

#[Route('/dashboard/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ProfileManager $profileManager,
        private CandidatManager $candidatManager,
        private NotificationManager $notificationManager,
        private JobListingRepository $jobListingRepository,
        private TypeContratRepository $typeContratRepository,
        private RequestStack $requestStack,
        private ModerateurManager $moderateurManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    private function checkCandidat()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if (!$candidat instanceof CandidateProfile){ 
            return $this->redirectToRoute('app_profile');
        }

        return null;
    }

    #[Route('/', name: 'app_dashboard_candidat')]
    public function index(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        return $this->redirectToRoute('app_dashboard_candidat_annonce');

        $candidat = $user->getCandidateProfile();
        $now = new DateTime();

        $monday = clone $now;
        $monday->modify('this monday');
        $sunday = clone $monday;
        $sunday->modify('+6 days');

        $formatMonday = $monday->format('d');
        $formatSunday = $sunday->format('d F Y');

        $form = $this->createForm(AnnonceSearchType::class);
        $form->handleRequest($request);
        $data = $this->jobListingRepository->findAll();
        $annonces = $this->candidatManager->annoncesCandidatDefaut($candidat);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('query')->getData();
            // $typeContrat = $form->get('typeContrat')->getData();
            $data = $this->searchPostings($searchTerm, $this->em);
        }

        return $this->render('dashboard/candidat/index.html.twig', [
            'identity' => $candidat,
            'annonces' => $annonces,
            'formatMonday' => $formatMonday,
            'formatSunday' => $formatSunday,
            'form' => $form->createView(),
            'postings' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    private function searchPostings(string $query = null, EntityManagerInterface $entityManager): array
    {
        if (empty($query)) {
            return $this->jobListingRepository->findAllJobListingPublished();
        }

        $qb = $entityManager->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];

        $conditions = [];
        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(p.titre LIKE :query' . $key .
                ' OR p.description LIKE :query' . $key .
                ' OR sec.nom LIKE :query' . $key .
                ' OR lang.nom LIKE :query' . $key .
                ' OR ts.nom LIKE :query' . $key . ')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        // if (!empty($typeContrat)) {
        //     array_merge($parameters, ['typeContrat' => $typeContrat->getNom()]);
        // }

        $qb->select('p')
            ->from('App\Entity\Entreprise\JobListing', 'p')
            ->leftJoin('p.secteur', 'sec')
            ->leftJoin('p.competences', 'ts')
            ->leftJoin('p.langues', 'lang')
            ->where(implode(' AND ', $conditions))
            ->andWhere('p.status = :status')
            ->setParameters(array_merge($parameters, ['status' => JobListing::STATUS_PUBLISHED]));

        return $qb->getQuery()->getResult();
    }


    #[Route("/profil", name: "profil")]

    public function profil(): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        return $this->render('dashboard/candidat/profil.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_candidat_annonce')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface ): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $typesContrat = $this->typeContratRepository->findAll();

        $form = $this->createForm(CandidatAnnonceSearchType::class, null, [
            'types_contrat' => $typesContrat,
        ]);

        $form->handleRequest($request);
        $data = $this->jobListingRepository->findAllJobListingPublished();
        // $annonces = $this->candidatManager->annoncesCandidatDefaut($candidat);
        if ($form->isSubmitted() && $form->isValid()) {
            // dd($form->getData());
            $titre = $form->get('titre')->getData();
            $typeContratObjet = $form->get('typeContrat')->getData();
            $typeContrat = $typeContratObjet ? $typeContratObjet->getNom() : null; 
            $lieu = $form->get('lieu')->getData();
            $competences = $form->get('competences')->getData();
            $data = $this->candidatManager->searchAnnonce($titre, $lieu, $typeContrat, $competences);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                dump($data);
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/candidat/annonces/_annonces.html.twig', [
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

        return $this->render('dashboard/candidat/annonces/annonces.html.twig', [
            'identity' => $candidat,
            'form' => $form->createView(),
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            // 'postings' => $data,
            'result' => $data,
        ]);
    }

    #[Route('/annonce/{jobId}', name: 'app_dashboard_candidat_annonce_show')]
    public function showAnnonce(Request $request, JobListing $annonce): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        $application = new Applications();
        $application->setDateCandidature(new DateTime());
        $application->setAnnonce($annonce);
        $application->setCandidat($candidat);
        $application->setStatus(Applications::STATUS_PENDING);
        $form = $this->createForm(ApplicationsType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($application);
            $this->em->flush();
    
            /** Envoi email moderateur */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Une nouvelle candidature sur Olona Talents",
                "moderateur/notification_candidature.html.twig",
                [
                    'user' => $candidat->getCandidat(),
                    'candidature' => $application,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
    
            /** Envoi email candidat */
            $this->mailerService->send(
                $user->getEmail(),
                "Votre candidature a été prise en compte sur Olona Talents",
                "candidat/notification_candidature.html.twig",
                [
                    'user' => $candidat->getCandidat(),
                    'candidature' => $application,
                    'objet' => "mise à jour",
                    'details_annonce' => $annonce,
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_annonces', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Notification candidat */
            $notification = $this->notificationManager->init();
            $notification->setDestinataire($candidat->getCandidat());
            $notification->setContenu("Le dépôt de votre candidature sur l'annonce " . $annonce->getTitre() . " a été pris en compte et en cours d'examen.");
            $notification->setTitre("Depôt candidature pris en compte et en cours d'examen.");
            $notification->setStatus(Notification::TYPE_ANNONCE);
            $this->em->persist($notification);
            $this->em->flush();

            $this->addFlash('success', "Candidature envoyé ");


            return $this->redirectToRoute('app_dashboard_candidat_annonce');
        }

        if ($annonce) {
            $ipAddress = $request->getClientIp();
            $viewRepository = $this->em->getRepository(AnnonceVues::class);
            $existingView = $viewRepository->findOneBy([
                'annonce' => $annonce,
                'idAdress' => $ipAddress,
            ]);
    
            if (!$existingView) {
                $view = new AnnonceVues();
                $view->setAnnonce($annonce);
                $view->setIdAdress($ipAddress);
    
                $this->em->persist($view);
                $annonce->addAnnonceVue($view);
                $this->em->flush();
            }
        }

        return $this->render('dashboard/candidat/annonces/show.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{jobId}/details', name: 'app_dashboard_candidat_annonce_details')]
    public function detailsAnnonce(Request $request, JobListing $annonce): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        return $this->render('dashboard/candidat/annonces/details.html.twig', [
            'annonce' => $annonce,
            'candidat' => $candidat,
        ]);
    }

    #[Route('/all/annonces', name: 'app_dashboard_candidat_annonces')]
    public function allAnnonces(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        return $this->render('dashboard/candidat/candidature/index.html.twig', [
            'pendings' => $paginatorInterface->paginate(
                $this->candidatManager->getPendingApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'accepteds' => $paginatorInterface->paginate(
                $this->candidatManager->getAcceptedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'refuseds' => $paginatorInterface->paginate(
                $this->candidatManager->getRefusedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
            'archiveds' => $paginatorInterface->paginate(
                $this->candidatManager->getArchivedApplications($candidat),
                $request->query->getInt('page', 1),
                10
            ),
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_candidat_notifications')]
    public function notifications(Request $request): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();


        return $this->render('dashboard/candidat/notification/index.html.twig', [
            'notifications' => $user->getRecus(),
        ]);
    }

    #[Route('/compte', name: 'app_dashboard_candidat_compte')]
    public function compte(Request $request): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        $formOne = $this->createForm(EditStepOneType::class, $candidat);
        $formTwo = $this->createForm(EditStepTwoType::class, $candidat);
        $formThree = $this->createForm(EditStepThreeType::class, $candidat);
        $formOne->handleRequest($request);
        $formTwo->handleRequest($request);
        $formThree->handleRequest($request);

        if ($formOne->isSubmitted() && $formOne->isValid()) {
            $this->em->persist($candidat);
            $this->em->flush();
        }

        if ($formTwo->isSubmitted() && $formTwo->isValid()) {
            $this->em->persist($candidat);
            $this->em->flush();
        }

        if ($formThree->isSubmitted() && $formThree->isValid()) {
            $this->em->persist($candidat);
            $this->em->flush();
        }

        return $this->render('dashboard/candidat/compte.html.twig', [
            'form_one' => $formOne->createView(),
            'form_two' => $formTwo->createView(),
            'form_three' => $formThree->createView(),
            'candidat' => $candidat,
            'experiences' => $candidat->getExperiences(),
            'competences' => $candidat->getCompetences(),
        ]);
    }

    #[Route('/guides/astuce', name: 'app_dashboard_guides_astuce')]
    public function astuce(): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        
        return $this->render('dashboard/candidat/guides/astuce.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/lettre-de-motivation', name: 'app_dashboard_guides_motivation')]
    public function motivation(): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        
        return $this->render('dashboard/candidat/guides/motivation.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/cv', name: 'app_dashboard_guides_cv')]
    public function cv(): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        return $this->render('dashboard/candidat/guides/cv.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/reseautage', name: 'app_dashboard_guides_reseautage')]
    public function reseautage(): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        return $this->render('dashboard/candidat/guides/reseautage.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

}
