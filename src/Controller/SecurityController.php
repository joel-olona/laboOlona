<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/connect', name: 'app_connect')]
    public function connect(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
    
        // if (null === $user || !$user->getIdentity() instanceof Identity) {
        //     return $this->redirectToRoute('app_identity_create');
        // }
    
        // /** @var Identity $identity */
        // $identity = $user->getIdentity();
    
        // if ($identity->getCompany() instanceof Company) {
        //     return $this->redirectToRoute('app_dashboard_company');
        // }
    
        // if ($identity->getExpert() instanceof Expert) {
        //     return $this->redirectToRoute('app_dashboard_expert');
        // }
    
        return $this->redirectToRoute('app_profile');
    }
}
