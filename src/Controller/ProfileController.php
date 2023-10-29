<?php

namespace App\Controller;

use App\Entity\EntrepriseProfile;
use App\Entity\Enum\TypeUser;
use App\Form\Profile\AccountType;
use App\Manager\ProfileManager;
use App\Service\Mailer\MailerService;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
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
    
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request): Response
    {
        $user = $this->userService->getCurrentUser();
        $form = $this->createForm(AccountType::class, $user,[]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->em->persist($user);
            $this->em->flush();
            $this->mailerService->send(
                $user->getEmail(),
                "Bienvenue sur Olona Talents",
                "welcome.html.twig",
                [
                    'user' => $user,
                    'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            if($user->getType() !== TypeUser::Entreprise ) return $this->redirectToRoute('app_profile_company', []);
            
            return $this->redirectToRoute('app_profile_candidate_step_one', []);
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/company', name: 'app_profile_company')]
    public function company(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $company = $this->userType();

        if (!$company instanceof EntrepriseProfile) {
            $company = $this->profileManager->createCompany($user);
        }

        $form = $this->createForm(CompanyType::class, $company, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $company = $form->getData();
            $this->em->persist($company);
            $this->em->flush();

            return $this->redirectToRoute('app_identity_confirmation', []);
        }

        return $this->render('identity/company.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/candidate/step-one', name: 'app_profile_candidate_step_one')]
    public function candidateStepOne(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $company = $this->userType();

        if (!$company instanceof EntrepriseProfile) {
            $company = $this->profileManager->createCompany($user);
        }

        $form = $this->createForm(CompanyType::class, $company, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $company = $form->getData();
            $this->em->persist($company);
            $this->em->flush();

            return $this->redirectToRoute('app_identity_confirmation', []);
        }

        return $this->render('identity/company.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    private function userType()
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        
        switch ($user->getType()) {
            case TypeUser::Candidat:
                $profile = $user->getCandidateProfile();
                break;
            
            case TypeUser::Entreprise:
                $profile = $user->getEntrepriseProfile();
                break;

            case TypeUser::Moderateur:
                $profile = $user->getModerateurProfile();
                break;

            default:
                $profile = null;
                break;
        }

        return $profile;
    }
}
