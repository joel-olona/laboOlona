<?php
namespace App\Security;

use DateTime;
use DateInterval;
use App\Entity\User; 
use App\Manager\IdentityManager;
use App\Service\User\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\Mailer\MailerService as MailerMailerService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{

    public function __construct(
        private ClientRegistry $clientRegistry, 
        private EntityManagerInterface $em, 
        private RouterInterface $router,
        // private IdentityManager $identityManager,
        private TokenGeneratorInterface $tokenGeneratorInterface,
        // private MailerMailerService $mailerService,
        private RequestStack $requestStack,
    )
    {
        
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google_main');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $typology = $this->requestStack->getSession()->get('typology', null);

                $email = $googleUser->getEmail();
                $tokenRegistration = $this->tokenGeneratorInterface->generateToken();

                // have they logged in with Google before? Easy!
                $existingUser = $this->em->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);
                if(!$existingUser){
                    $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $googleUser->getEmail()]);
                }
                //User doesnt exist, we create it !
                $new = false;
                if (!$existingUser) {
                    $new = true;
                    $existingUser = new User();
                    $existingUser->setEmail($email);
                    $existingUser->setDateInscription(new DateTime());
                    // $existingUser->setTokenRegistration($tokenRegistration);
                }
                $existingUser->setGoogleId($googleUser->getId());
                $existingUser->setPrenom($googleUser->getFirstName());
                $existingUser->setNom($googleUser->getLastName());
                $existingUser->setGravatar($googleUser->getAvatar());
                // $existingUser->setTokenRegistration($tokenRegistration);
                $this->em->persist($existingUser);
                $this->em->flush();
                // if($new){                    
                //     // send an email
                //     $this->mailerService->send(
                //         $email,
                //         "Confirmation du compte utilisateur",
                //         "registration_email.html.twig",
                //         [
                //             'user' => $existingUser,
                //             'token' => $tokenRegistration,
                //             'url' => $this->router->generate('account_verify', ['token' => $tokenRegistration, 'id' => $existingUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                //             'lifeTimeToken' => $existingUser->getTokenLifeTime()->format('d/M/Y à Hh:i')
                //         ]
                //     );
                //     $this->requestStack->getSession()->getFlashBag()->add('info', 'Votre compte a bien été crée, veuillez vérifier vos e-mails pour l\'activer.');
                // }

    

                return $existingUser;
            })
        );
    }
    
    public function supports(Request $request) : bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function onAuthenticationSuccess(
        Request $request, 
        TokenInterface $token, 
        $providerKey) : Response
    {
        $targetUrl = $this->router->generate('app_connect');

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) : Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}