<?php

namespace App\Controller;

use App\Entity\CandidateProfile;
use App\Entity\User;
use App\Entity\Enum\TypeUser;
use App\Manager\ProfileManager;
use App\Entity\EntrepriseProfile;
use App\Form\Profile\AccountType;
use App\Form\Profile\Candidat\StepOneType;
use App\Form\Profile\Candidat\StepTwoType;
use App\Service\User\UserService;
use App\Form\Profile\EntrepriseType;
use App\Service\Mailer\MailerService;
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
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        if (null === $user || $user->getType() === User::ACCOUNT_CANDIDAT) {
            return $this->redirectToRoute('app_profile_candidate_step_one');
        }
        if (null === $user || $user->getType() === User::ACCOUNT_ENTREPRISE) {
            return $this->redirectToRoute('app_profile_entreprise');
        }
        if (null === $user || $user->getType() === User::ACCOUNT_MODERATEUR) {
            return $this->redirectToRoute('app_profile_create');
        }
        
        return $this->redirectToRoute('app_profile_account', []);
    }
    
    #[Route('/profile/account', name: 'app_profile_account')]
    public function account(Request $request): Response
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

            if($user->getType() !== User::ACCOUNT_CANDIDAT ) return $this->redirectToRoute('app_profile_entreprise', []);
            
            return $this->redirectToRoute('app_profile_candidate_step_one', []);
        }

        return $this->render('profile/account.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/company', name: 'app_profile_entreprise')]
    public function company(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $company = $this->userType();

        if (!$company instanceof EntrepriseProfile) {
            $company = $this->profileManager->createCompany($user);
        }

        $form = $this->createForm(EntrepriseType::class, $company, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->profileManager->saveForm($form);

            return $this->redirectToRoute('app_profile_confirmation', []);
        }

        return $this->render('profile/entreprise.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/candidate/step-one', name: 'app_profile_candidate_step_one')]
    public function candidateStepOne(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        if (!$candidat instanceof CandidateProfile) {
            $candidat = $this->profileManager->createCandidat($user);
        }

        $form = $this->createForm(StepOneType::class, $candidat, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $candidat = $form->getData();
            $this->em->persist($candidat);
            $this->em->flush();

            return $this->redirectToRoute('app_profile_candidate_step_two', []);
        }

        return $this->render('profile/candidat/step-one.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/candidate/step-two', name: 'app_profile_candidate_step_two')]
    public function candidateStepTwo(Request $request): Response
    {
        /** @var $user User */
        $user = $this->userService->getCurrentUser();
        $candidat = $user->getCandidateProfile();

        $form = $this->createForm(StepTwoType::class, $candidat, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->profileManager->saveForm($form);

            return $this->redirectToRoute('app_profile_confirmation', []);
        }

        return $this->render('profile/candidat/step-two.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
            'competences' => $candidat->getCompetences(),
            'experiences' => $candidat->getExperiences(),
        ]);
    }

    #[Route('/profile/confirmation', name: 'app_profile_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('profile/confirmation.html.twig', [
            'controller_name' => 'profileController',
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
