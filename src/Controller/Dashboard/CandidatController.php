<?php

namespace App\Controller\Dashboard;

use App\Entity\CandidateProfile;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/', name: 'app_dashboard_candidat')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        if(!$user->getCandidateProfile() instanceof CandidateProfile)
        return $this->redirectToRoute('app_profile');
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
    
}
