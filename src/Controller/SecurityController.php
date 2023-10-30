<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    
        if (null === $user || $user->getType() === User::ACCOUNT_MODERATEUR) {
            return $this->redirectToRoute('app_dashboard_moderateur');
        }
    
        if (null === $user || $user->getType() === User::ACCOUNT_ENTREPRISE) {
            return $this->redirectToRoute('app_dashboard_entreprise');
        }
    
        if (null === $user || $user->getType() === User::ACCOUNT_CANDIDAT) {
            return $this->redirectToRoute('app_dashboard_candidat');
        }
    
        return $this->redirectToRoute('app_profile');
    }
}
