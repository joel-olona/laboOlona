<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Entity\User;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
use App\Service\Mailer\MailerService;
use App\Form\Search\AnnonceSearchType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Profile\Candidat\StepOneType;
use App\Form\Profile\Candidat\StepTwoType;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\Profile\Candidat\StepThreeType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Profile\Candidat\Edit\StepOneType as EditStepOneType;
use App\Form\Profile\Candidat\Edit\StepTwoType as EditStepTwoType;
use App\Form\Profile\Candidat\Edit\StepThreeType as EditStepThreeType;


#[Route('/dashboard/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ProfileManager $profileManager,
        private CandidatManager $candidatManager,
        private JobListingRepository $jobListingRepository,
        private RequestStack $requestStack,
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
            return [];
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

        $qb->select('p')
            ->from('App\Entity\Entreprise\JobListing', 'p')
            ->leftJoin('p.secteur', 'sec')
            ->leftJoin('p.competences', 'ts')
            ->leftJoin('p.langues', 'lang')
            ->where(implode(' OR ', $conditions))
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
        $searchTerm = "";

        $form = $this->createForm(AnnonceSearchType::class);
        $form->handleRequest($request);
        $data = $this->jobListingRepository->findAll();
        $annonces = $this->candidatManager->annoncesCandidatDefaut($candidat);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('query')->getData();
            $data = $this->searchPostings($searchTerm, $this->em);
        }

        return $this->render('dashboard/candidat/annonces/annonces.html.twig', [
            'identity' => $candidat,
            'form' => $form->createView(),
            'postings' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'words' => explode(' ', $searchTerm),
        ]);
    }

    #[Route('/annonce/{jobId}', name: 'app_dashboard_candidat_annonce_show')]
    public function showAnnonce(JobListing $annonce): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        return $this->render('dashboard/candidat/annonces/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/all/annonces', name: 'app_dashboard_candidat_annonces')]
    public function allAnnonces(): Response
    {
        $redirection = $this->checkCandidat();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/candidat/annonces/all.html.twig', [
            'controller_name' => 'GuidesController',
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
