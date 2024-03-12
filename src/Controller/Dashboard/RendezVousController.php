<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use Symfony\Component\Uid\Uuid;
use App\Manager\CandidatManager;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Referrer\Referral;
use App\Service\User\UserService;
use App\Entity\Moderateur\Metting;
use App\Manager\ModerateurManager;
use App\Manager\RendezVousManager;
use App\Form\Moderateur\MettingType;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\Assignation;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\AssignationRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGenerator;

#[Route('/dashboard/rendez-vous')]
class RendezVousController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private CandidatManager $candidatManager,
        private RendezVousManager $rendezVousManager,
        private MettingRepository $mettingRepository,
        private ApplicationsRepository $applicationsRepository,
        private AssignationRepository $assignationRepository,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private PaginatorInterface $paginatorInterface,
    ) {}
    
    #[Route('/', name: 'rendezvous_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $role = $this->rendezVousManager->getUserRole($user);

        return $this->render('dashboard/rendez_vous/index.html.twig', [
            'rendezvousList' => $this->rendezVousManager->findMettingByRole($role),
            'role' => $user->getType(),
        ]);
    }
    
    #[Route('/create', name: 'rendezvous_create')]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $candidature = $this->applicationsRepository->find($request->query->get('candidature', ''));
        $assignation = $this->assignationRepository->find($request->query->get('assignation', ''));

        if($candidature instanceof Applications){
            $rendezVous = $this->rendezVousManager->createRendezVous($user->getModerateurProfile(), $candidature->getCandidat(), $candidature->getAnnonce()->getEntreprise(), $candidature->getAnnonce());
        }else if($assignation instanceof Assignation){
            $rendezVous = $this->rendezVousManager->createRendezVous($user->getModerateurProfile(), $assignation->getProfil(), $assignation->getJobListing()->getEntreprise(), $assignation->getJobListing());
        }else{
            $rendezVous = new Metting();
            $rendezVous->setStatus(Metting::STATUS_PENDING);
            $rendezVous->setCustomId(new Uuid(Uuid::v4()));
            $rendezVous->setLieu('meet.olona-talents.com');
        }

        $form = $this->createForm(MettingType::class, $rendezVous);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rendezVous = $form->getData();
            $refered = $this->em->getRepository(Referral::class)->findOneBy(['referredEmail' => $rendezVous->getCandidat()->getCandidat()->getEmail()]);
            $customId = $rendezVous->getCustomId();
            $rendezVous->setLink($this->urlGenerator->generate(
                'app_dashboard_conference', 
                [
                    'uuid' => $customId
                ], 
                UrlGenerator::ABSOLUTE_URL
                ));

            if($refered instanceof Referral){
                $refered->setStep(5);
                $this->em->persist($refered);
            }
            $this->em->persist($rendezVous);
            $this->em->flush();

            /** Envoi mail candidat */
            $this->mailerService->send(
                $rendezVous->getCandidat()->getCandidat()->getEmail(),
                "Vous avez un rendez-vous pour un entretien sur Olona Talents",
                "candidat/notification_rendezvous.html.twig",
                [
                    'user' => $rendezVous->getCandidat()->getCandidat(),
                    'rendezvous' => $rendezVous,
                    'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Envoi mail entreprise */
            $this->mailerService->send(
                $rendezVous->getEntreprise()->getEntreprise()->getEmail(),
                "Vous avez un rendez-vous pour un entretien sur Olona Talents",
                "entreprise/notification_rendezvous.html.twig",
                [
                    'user' => $rendezVous->getEntreprise()->getEntreprise(),
                    'rendezvous' => $rendezVous,
                    'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            $this->addFlash('success', 'Rendez-vous sauvegarder');

            return  $this->redirectToRoute('app_dashboard_moderateur_mettings', []);
        }

        return $this->render('dashboard/rendez_vous/create.html.twig', [
            'rendezvousList' => $this->moderateurManager->findAllOrderDesc($this->mettingRepository),
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'rendezvous_show')]
    public function show(Request $request, Metting $rendezvous): Response
    {
        return $this->render('dashboard/rendez_vous/show.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'rendezvous_edit')]
    public function edit(Request $request, Metting $rendezvous): Response
    {
        $form = $this->createForm(MettingType::class, $rendezvous);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rendezVous = $form->getData();
            /** @var User $user */
            $user = $this->userService->getCurrentUser();
            $role = $this->rendezVousManager->getUserRole($user);
            if($role instanceof ModerateurProfile){
                /** Seuls les moderateurs peuvent mettre à jour */
                $this->em->persist($rendezVous);
                $this->em->flush();
                /** Envoi mail candidat & entrepise */
                $this->mailerService->send(
                    $rendezVous->getEntreprise()->getEntreprise()->getEmail(),
                    "Reprogrammation rendez-vous",
                    "moderateur/modification_rendezvous.html.twig",
                    [
                        'user' => $rendezVous->getEntreprise()->getEntreprise(),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
                $this->mailerService->send(
                    $rendezVous->getCandidat()->getCandidat()->getEmail(),
                    "Reprogrammation rendez-vous",
                    "moderateur/modification_rendezvous.html.twig",
                    [
                        'user' => $rendezVous->getCandidat()->getCandidat(),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

                $this->addFlash('success', 'Cangement de rendez-vous envoyée au candidat et à l\'entreprise');
            }else{
                /** Envoi mail aux modérateurs */
                $this->mailerService->sendMultiple(
                    $this->moderateurManager->getModerateurEmails(),
                    "Demande de reprogrammation rendez-vous par un ".$this->rendezVousManager->getUserTypeByRole($role),
                    "moderateur/reprogrammation_rendezvous.html.twig",
                    [
                        'user' => $this->rendezVousManager->getUserByRole($role),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

                $this->addFlash('success', 'Demande de changement de rendez-vous envoyée aux modérateur');

            }

            return  $this->redirectToRoute('rendezvous_index', []);
        }

        return $this->render('dashboard/rendez_vous/edit.html.twig', [
            'rendezvous' => $rendezvous,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'rendezvous_delete')]
    public function delete(Request $request, Metting $rendezvous): Response
    {
        return $this->render('dashboard/rendez_vous/index.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }
    
    #[Route('/{id}/send-invitation', name: 'rendezvous_send_invitation')]
    public function sendInvitation(Request $request, Metting $rendezvous): Response
    {
        // $notification = new Notification();
        // $notification->setExpediteur($rendezvous->getModerateur()->getModerateur());
        // $notification->setDestinataire($rendezvous->getCandidat()->getCandidat());
        

        return $this->render('dashboard/rendez_vous/invitation.html.twig', [
            'rendezvous' => $rendezvous,
            'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezvous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
    
    #[Route('/sync-calendar', name: 'rendezvous_sync_calendar')]
    public function sync(): Response
    {
        return $this->render('dashboard/rendez_vous/index.html.twig', [
            'rendezvousList' => $this->moderateurManager->findAllOrderDesc($this->mettingRepository),
        ]);
    }
    
    #[Route('/{id}/send-reminder', name: 'rendezvous_send_reminder')]
    public function sendReminder(Request $request, Metting $rendezVous): Response
    {
        $to = $request->query->get('to', null);
        if($to !== null){
            if($to === 'entreprise'){
                /** Envoi mail entrepise */
                $this->mailerService->send(
                    $rendezVous->getEntreprise()->getEntreprise()->getEmail(),
                    "Rappel de votre Entretien",
                    "entreprise/rappel_rendezvous.html.twig",
                    [
                        'user' => $rendezVous->getEntreprise()->getEntreprise(),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
            }else{
                /** Envoi mail candidat */
                $this->mailerService->send(
                    $rendezVous->getCandidat()->getCandidat()->getEmail(),
                    "Rappel de votre Entretien",
                    "candidat/rappel_rendezvous.html.twig",
                    [
                        'user' => $rendezVous->getCandidat()->getCandidat(),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
            }
        }else{
            /** Envoi mail candidat & entrepise */
            $this->mailerService->send(
                $rendezVous->getEntreprise()->getEntreprise()->getEmail(),
                "Rappel de votre Entretien",
                "entreprise/rappel_rendezvous.html.twig",
                [
                    'user' => $rendezVous->getEntreprise()->getEntreprise(),
                    'rendezvous' => $rendezVous,
                    'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->mailerService->send(
                $rendezVous->getCandidat()->getCandidat()->getEmail(),
                "Rappel de votre Entretien",
                "candidat/rappel_rendezvous.html.twig",
                [
                    'user' => $rendezVous->getCandidat()->getCandidat(),
                    'rendezvous' => $rendezVous,
                    'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

        }

        $this->addFlash('success', 'Un emal de rappel a été envoyé');
        $referer = $request->headers->get('referer');

        return $referer ? $this->redirect($referer) : $this->redirectToRoute('rendezvous_index');
    }
    
    #[Route('/{id}/reschedule', name: 'rendezvous_reschedule')]
    public function reschedule(Request $request, Metting $rendezvous): Response
    {
        $form = $this->createForm(MettingType::class, $rendezvous);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rendezVous = $form->getData();
            /** @var User $user */
            $user = $this->userService->getCurrentUser();
            $role = $this->rendezVousManager->getUserRole($user);
            if($role instanceof ModerateurProfile){
                /** Seuls les moderateurs peuvent mettre à jour */
                $this->em->persist($rendezVous);
                $this->em->flush();
                /** Envoi mail candidat & entrepise */
                $this->mailerService->send(
                    $rendezVous->getEntreprise()->getEntreprise()->getEmail(),
                    "Reprogrammation rendez-vous",
                    "moderateur/modification_rendezvous.html.twig",
                    [
                        'user' => $rendezVous->getEntreprise()->getEntreprise(),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );
                $this->mailerService->send(
                    $rendezVous->getCandidat()->getCandidat()->getEmail(),
                    "Reprogrammation rendez-vous",
                    "moderateur/modification_rendezvous.html.twig",
                    [
                        'user' => $rendezVous->getCandidat()->getCandidat(),
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

                $this->addFlash('success', 'Cangement de rendez-vous envoyée au candidat et à l\'entreprise');
            }else{
                /** Envoi mail aux modérateurs */
                $this->mailerService->sendMultiple(
                    $this->moderateurManager->getModerateurEmails(),
                    "Demande de reprogrammation rendez-vous par un ".$this->rendezVousManager->getUserTypeByRole($role),
                    "moderateur/reprogrammation_rendezvous.html.twig",
                    [
                        'user' => $role,
                        'rendezvous' => $rendezVous,
                        'confirmationLink' => $this->urlGenerator->generate('rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

                $this->addFlash('success', 'Demande de changement de rendez-vous envoyée aux modérateur');

            }

            return  $this->redirectToRoute('rendezvous_index', []);
        }

        return $this->render('dashboard/rendez_vous/reschedule.html.twig', [
            'rendezvous' => $rendezvous,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/entreprise/{id}', name: 'rendezvous_entreprise')]
    public function entreprise(Request $request, EntrepriseProfile $entreprise): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux modérateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = $entreprise->getMettings();
        
        return $this->render('dashboard/moderateur/metting/entreprise.html.twig', [
            'annonces' => $this->paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'entreprise' => $entreprise,
            // 'form' => $form->createView(),
        ]);
    }
}
