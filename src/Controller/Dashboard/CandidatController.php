<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Form\Search\AnnonceSearchType;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/dashboard/candidat')]
class CandidatController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ProfileManager $profileManager,
        private CandidatManager $candidatManager,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ){
    }

    private function checkCandidat()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        if (!$candidat instanceof CandidateProfile) return $this->redirectToRoute('app_profile');
    }

    #[Route('/', name: 'app_dashboard_candidat')]
    public function index(Request $request): Response
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

        return $this->render('dashboard/candidat/index.html.twig', [
            'identity' => $candidat,
            'annonces' => $this->candidatManager->annoncesCandidatDefaut($candidat),
            'formatMonday' => $formatMonday,
            'formatSunday' => $formatSunday,
            'form' => $form->createView(),
        ]);

        return $this->render('dashboard/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }

    private function searchPostings(string $query, EntityManagerInterface $entityManager): array
    {
        if(empty($query)){
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

    #[Route('/annonces', name: 'app_dashboard_candidat_annonce')]
    public function annonces(Request $request): Response
    {
        $this->checkCandidat();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();
        $searchTerm = "";
        
        $form = $this->createForm(AnnonceSearchType::class);
        $form->handleRequest($request);
        $postings = $this->candidatManager->annoncesCandidat($candidat);
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('query')->getData();
            $postings = $this->searchPostings($searchTerm, $this->em);
        }

        return $this->render('dashboard/candidat/annonces/annonces.html.twig', [
            'identity' => $candidat,
            'form' => $form->createView(),
            // 'recomanded_postings' => $this->postingManager->findExpertAnnouncements($expert),
            'postings' => $postings,
            'words' => explode(' ', $searchTerm),
        ]);
    }

    #[Route('/annonce/{jobId}', name: 'app_dashboard_candidat_annonce_show')]
    public function showAnnonce(JobListing $annonce): Response
    {

        $this->checkCandidat();
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
        return $this->render('dashboard/candidat/annonces/all.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/compte', name: 'app_dashboard_candidat_compte')]
    public function compte(): Response
    {
        return $this->render('dashboard/candidat/compte.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }

    #[Route('/guides/lettre-de-motivation', name: 'app_dashboard_guides_motivation')]
    public function motivation(): Response
    {
        return $this->render('dashboard/candidat/motivation.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/cv', name: 'app_dashboard_guides_cv')]
    public function cv(): Response
    {
        return $this->render('dashboard/candidat/cv.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }

    #[Route('/guides/reseautage', name: 'app_dashboard_guides_reseautage')]
    public function reseautage(): Response
    {
        return $this->render('dashboard/candidat/reseautage.html.twig', [
            'controller_name' => 'GuidesController',
        ]);
    }
    
}
