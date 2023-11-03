<?php

namespace App\Controller\Dashboard;

use DateTime;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Form\Search\AnnonceSearchType;
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
            // 'postings' => $this->postingManager->findExpertAnnouncements($expert),
            'formatMonday' => $formatMonday,
            'formatSunday' => $formatSunday,
            'form' => $form->createView(),
        ]);

        return $this->render('dashboard/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }

    #[Route('/annonces', name: 'app_dashboard_candidat_annonce')]
    public function annonces(): Response
    {
        return $this->render('dashboard/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }

    #[Route('/compte', name: 'app_dashboard_candidat_compte')]
    public function compte(): Response
    {
        return $this->render('dashboard/candidat/index.html.twig', [
            'controller_name' => 'CandidatController',
        ]);
    }
    
}
