<?php

namespace App\Controller\Dashboard;

use App\Entity\User;
use App\Form\MettingType;
use App\Entity\Notification;
use App\Manager\CandidatManager;
use App\Service\User\UserService;
use App\Entity\Moderateur\Metting;
use App\Manager\ModerateurManager;
use App\Manager\RendezVousManager;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }
    
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
        $rendezvous = new Metting();
        $form = $this->createForm(MettingType::class, $rendezvous);
        $form->handleRequest($request);

        return $this->render('dashboard/rendez_vous/create.html.twig', [
            'rendezvousList' => $this->mettingRepository->findAll(),
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
            'rendezvousList' => $this->mettingRepository->findAll(),
        ]);
    }
    
    #[Route('/{id}/send-reminder', name: 'rendezvous_send_reminder')]
    public function sendReminder(Request $request, Metting $rendezvous): Response
    {
        return $this->render('dashboard/rendez_vous/reminder.html.twig', [
            'rendezvous' => $rendezvous,
        ]);
    }
    
    #[Route('/{id}/reschedule', name: 'rendezvous_reschedule')]
    public function reschedule(Request $request, Metting $rendezvous): Response
    {
        $form = $this->createForm(MettingType::class, $rendezvous);
        $form->handleRequest($request);

        return $this->render('dashboard/rendez_vous/reschedule.html.twig', [
            'rendezvous' => $rendezvous,
            'form' => $form->createView(),
        ]);
    }
}
