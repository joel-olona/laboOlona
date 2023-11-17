<?php

namespace App\Controller\Dashboard;

use App\Entity\Secteur;
use App\Form\MettingType;
use App\Manager\ProfileManager;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Entity\Moderateur\Metting;
use App\Manager\ModerateurManager;
use App\Form\Moderateur\SecteurType;
use App\Entity\Entreprise\JobListing;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\TypeContrat;
use App\Entity\Notification;
use App\Form\Moderateur\NotificationType;
use App\Form\Search\Secteur\SecteurSearchType;
use App\Form\Moderateur\TypeContratType;
use App\Form\Search\Annonce\ModerateurAnnonceEntrepriseSearchType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Search\TypeContrat\TypeContratSearchType;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Search\Annonce\ModerateurAnnonceSearchType;
use App\Form\Search\Candidat\ModerateurCandidatSearchType;
use App\Form\Search\Candidature\ModerateurCandidatureSearchType;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\Search\Entreprise\ModerateurEntrepriseSearchType;
use App\Manager\CandidatManager;
use App\Manager\NotificationManager;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use App\Repository\Moderateur\TypeContratRepository;
use DateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/dashboard/moderateur')]
class ModerateurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
        private CandidatManager $candidatManager,
        private ProfileManager $profileManager,
        private NotificationManager $notificationManager,
        private SecteurRepository $secteurRepository,
        private TypeContratRepository $typeContratRepository,
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private MettingRepository $mettingRepository,
        private NotificationRepository $notificationRepository,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    private function checkModerateur()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();
        if (!$moderateur instanceof ModerateurProfile){ 
            return $this->redirectToRoute('app_profile');
        }

        return null;
    }

    #[Route('/', name: 'app_dashboard_moderateur')]
    public function index(): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/index.html.twig', [
            'secteurs' => $this->moderateurManager->findAllOrderDesc($this->secteurRepository),
            'typeContrats' => $this->moderateurManager->findAllOrderDesc($this->typeContratRepository),
            'annonces' => $this->moderateurManager->findAllOrderDesc($this->jobListingRepository),
            'annonces_pending' => $this->jobListingRepository->findBy(['status' => JobListing::STATUS_PENDING], ['id' => 'DESC']),
            'entreprises' => $this->moderateurManager->findAllOrderDesc($this->entrepriseProfileRepository),
            'candidats' => $this->moderateurManager->findAllOrderDesc($this->candidateProfileRepository),
            'candidats_pending' => $this->candidateProfileRepository->findBy(['status' => CandidateProfile::STATUS_PENDING], ['id' => 'DESC']),
            'mettings' => $this->moderateurManager->findAllOrderDesc($this->mettingRepository),
            'notifications' => $this->moderateurManager->findAllOrderDesc($this->notificationRepository),
        ]);
    }

    #[Route('/secteurs', name: 'app_dashboard_moderateur_secteur')]
    public function sectors(Request $request, SecteurRepository $secteurRepository, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche secteur */
        $form = $this->createForm(SecteurSearchType::class);
        $form->handleRequest($request);
        $data = $secteurRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('secteur')->getData();
            $data = $this->moderateurManager->searchSecteur($searchTerm);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/secteur/_secteurs.html.twig', [
                        'secteurs' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/secteur/index.html.twig', [
            'secteurs' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/secteur/new', name: 'app_dashboard_moderateur_new_secteur')]
    public function newSecteur(Request $request): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Initialiser une instance de Secteur */
        $secteur = $this->moderateurManager->initSector();
        $form = $this->createForm(SecteurType::class, $secteur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder TypeContrat */
            $secteur = $this->moderateurManager->saveSectorForm($form);
            $this->addFlash('success', 'Secteur sauvegardé');

            return $this->redirectToRoute('app_dashboard_moderateur_secteur', []);
        }

        return $this->render('dashboard/moderateur/secteur/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
        ]);
    }

    #[Route('/secteur/{slug}/edit', name: 'app_dashboard_moderateur_edit_secteur')]
    public function editSecteur(Request $request, Secteur $secteur): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** @var Secteur $secteur qui vient de {slug} */
        $form = $this->createForm(SecteurType::class, $secteur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Mettre à jour le Secteur */
            $secteur = $this->moderateurManager->saveSectorForm($form);
            $this->addFlash('success', 'Secteur mis à jour');

            return $this->redirectToRoute('app_dashboard_moderateur_secteur', []);
        }

        return $this->render('dashboard/moderateur/secteur/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Mettre à jour',
        ]);
    }

    #[Route('/secteur/supprimer/{slug}', name: 'app_dashboard_moderateur_delete_secteur')]
    public function deleteSecteur(Secteur $secteur): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Supprimer le Secteur */
        $this->moderateurManager->deleteSector($secteur);
        $this->addFlash('success', 'Secteur supprimé avec succès.');

        return $this->redirectToRoute('app_dashboard_moderateur_secteur');
    }

    #[Route('/type-contrat', name: 'app_dashboard_moderateur_type_contrat')]
    public function typeContrat(Request $request, TypeContratRepository $typeContratRepository, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche type de contrat */
        $form = $this->createForm(TypeContratSearchType::class);
        $form->handleRequest($request);
        $data = $typeContratRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $searchTerm = $form->get('typeContrat')->getData();
            $data = $this->moderateurManager->searchTypeContrat($searchTerm);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/type-contrat/_type_contrats.html.twig', [
                        'types_contrat' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }
        
        return $this->render('dashboard/moderateur/type-contrat/index.html.twig', [
            'types_contrat' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/type-contrat/new', name: 'app_dashboard_moderateur_new_type_contrat')]
    public function newTypeContrat(Request $request): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Initialiser une instance de TypeContrat */
        $typeContrat = $this->moderateurManager->initTypeContrat();
        $form = $this->createForm(TypeContratType::class, $typeContrat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder TypeContrat */
            $typeContrat = $this->moderateurManager->saveTypeContratForm($form);
            $this->addFlash('success', 'Type contrat sauvegardé');

            return $this->redirectToRoute('app_dashboard_moderateur_type_contrat', []);
        }

        return $this->render('dashboard/moderateur/type-contrat/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
        ]);
    }

    #[Route('/type-contrat/{slug}/edit', name: 'app_dashboard_moderateur_edit_type_contrat')]
    public function editTypeContrat(Request $request, TypeContrat $typeContrat): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** @var TypeContrat $typeContrat qui vient de {slug} */
        $form = $this->createForm(TypeContratType::class, $typeContrat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Mettre à jour le TypeContrat */
            $typeContrat = $this->moderateurManager->saveTypeContratForm($form);
            $this->addFlash('success', 'Type contrat mis à jour');

            return $this->redirectToRoute('app_dashboard_moderateur_type_contrat', []);
        }

        return $this->render('dashboard/moderateur/type-contrat/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Mettre à jour',
        ]);
    }

    #[Route('/type-contrat/supprimer/{slug}', name: 'app_dashboard_moderateur_delete_type_contrat')]
    public function deleteTypeContrat(TypeContrat $typeContrat): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Supprimer le TypeContrat */
        $this->moderateurManager->deleteTypeContrat($typeContrat);
        $this->addFlash('success', 'Type contrat supprimé avec succès.');

        return $this->redirectToRoute('app_dashboard_moderateur_type_contrat');
    }

    #[Route('/annonces', name: 'app_dashboard_moderateur_annonces')]
    public function annonces(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche annonces */
        $form = $this->createForm(ModerateurAnnonceSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllListingJob();
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $entreprise = $form->get('entreprise')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchAnnonce($titre, $entreprise, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/annonce/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/annonce/index.html.twig', [
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annonce/{id}', name: 'app_dashboard_moderateur_annonce_view', methods: ['GET'])]
    public function viewAnnonce(JobListing $annonce): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/annonce/view.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/annonce/{id}/candidature', name: 'app_dashboard_moderateur_annonce_candidature_view', methods: ['GET'])]
    public function viewCandidatureAnnonce(JobListing $annonce): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/annonce/candidature.html.twig', [
            'annonce' => $annonce,
            'candidatures' => $annonce->getApplications(),
        ]);
    }

    #[Route('/notifier/{annonce}/entreprise/{entreprise}', name: 'app_dashboard_moderateur_annonce_notifier')]
    public function notifierAnnonce(Request $request, JobListing $annonce, EntrepriseProfile $entreprise): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setExpediteur($this->userService->getCurrentUser());
        $notification->setDestinataire($entreprise->getEntreprise());
        $notification->setType(Notification::TYPE_ANNONCE);
        $notification->setIsRead(false);

        $form = $this->createForm(NotificationType::class, $notification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notification = $form->getData();
            $this->em->persist($notification);
            $this->em->flush();
            /** Envoi email à l'entreprise */
            $this->mailerService->send(
                $entreprise->getEntreprise()->getEmail(),
                "Statut de votre annonce sur Olona Talents",
                "notification_annonce.html.twig",
                [
                    'user' => $entreprise->getEntreprise(),
                    'details_annonce' => $notification->getContenu(),
                    'objet' => "est toujours en cours de moderation",
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_view_annonce', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );
            $this->addFlash('success', 'Un email a été envoyé à l\'entreprise');

            return $this->redirectToRoute('app_dashboard_moderateur_annonce_view', ['id' => $annonce->getId()]);
        }

        return $this->render('dashboard/moderateur/annonce/notify.html.twig', [
            'annonce' => $annonce,
            'entreprise' => $entreprise,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/status/annonce/{id}', name: 'change_status_annonce')]
    public function changeAnnonceStatus(Request $request, EntityManagerInterface $entityManager, JobListing $annonce): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, JobListing::getArrayStatuses())) {
            $annonce->setStatus($status);
            $entityManager->flush();
            /** Envoi email à l'entreprise si validée*/
            if($annonce->getStatus() === JobListing::STATUS_PUBLISHED || $annonce->getStatus() === JobListing::STATUS_FEATURED ){
                $this->mailerService->send(
                    $annonce->getEntreprise()->getEntreprise()->getEmail(),
                    "Statut de votre annonce sur Olona Talents",
                    "entreprise/notification_annonce.html.twig",
                    [
                        'user' => $annonce->getEntreprise()->getEntreprise(),
                        'details_annonce' => $annonce,
                        'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_view_annonce', ['id' => $annonce->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

                /** Notification candidat */
                $notification = $this->notificationManager->init();
                $notification->setDestinataire($annonce->getEntreprise()->getEntreprise());
                $notification->setContenu("Votre annonce " . $annonce->getTitre() . " a été validée par Olona Talents.");
                $notification->setTitre("Statut de votre annonce.");
                $notification->setStatus(Notification::TYPE_ANNONCE);
                $this->em->persist($notification);
                $this->em->flush();
            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_annonces');
    }

    #[Route('/delete/annonce/{id}', name: 'delete_annonce', methods: ['POST'])]
    public function deleteAnnonce(JobListing $annonce, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $entityManager->remove($annonce);
        $entityManager->flush();
        $this->addFlash('success', 'Annonce supprimée avec succès.');

        return $this->redirectToRoute('app_dashboard_moderateur_annonces');
    }

    #[Route('/details/annonce/{id}', name: 'details_annonce', methods: ['GET'])]
    public function detailsAnnonce(JobListing $annonce): JsonResponse
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $annonceDetails = [
            'titre' => $annonce->getTitre(),
            'description' => $annonce->getDescription(),
            'dateCreation' => $annonce->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateExpiration' => $annonce->getDateExpiration()?->format('Y-m-d H:i:s'),
            'status' => $annonce->getStatus(),
            'salaire' => $annonce->getSalaire(),
            'lieu' => $annonce->getLieu(),
            'typeContrat' => $annonce->getTypeContrat(),
        ];

        return $this->json($annonceDetails);
    }

    #[Route('/entreprises', name: 'app_dashboard_moderateur_entreprises')]
    public function entreprises(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllEntreprise();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $secteur = $form->get('secteur')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchEntreprise($nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_entreprises.html.twig', [
                        'entreprises' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/entreprise/index.html.twig', [
            'entreprises' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprise/{id}', name: 'voir_entreprise')]
    public function voirEntreprise(EntrepriseProfile $entreprise): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/entreprise_view.html.twig', [
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/supprimer/entreprise/{id}', name: 'supprimer_entreprise', methods: ['POST'])]
    public function supprimerEntreprise(Request $request, EntityManagerInterface $entityManager, EntrepriseProfile $entreprise): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        if ($this->isCsrfTokenValid('delete' . $entreprise->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entreprise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_entreprises');
    }

    #[Route('/entreprises/{id}/annonces', name: 'app_dashboard_moderateur_entreprises_annonces')]
    public function entreprisesAnnonces(Request $request, PaginatorInterface $paginatorInterface, EntrepriseProfile $entreprise, JobListingRepository $jobListingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche entreprise */
        $form = $this->createForm(ModerateurAnnonceEntrepriseSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllAnnonceByEntreprise($entreprise);
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $status = $form->get('status')->getData();
            $secteur = $form->get('secteur')->getData();
            $data = $this->moderateurManager->findAllAnnonceEntreprise($entreprise, $nom, $secteur, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/entreprise/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        $annonces = $jobListingRepository->findBy(['entreprise' => $entreprise]);

        return $this->render('dashboard/moderateur/entreprise/annonces.html.twig', [
            'annonces' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'entreprise' => $entreprise,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprise/{entreprise_id}/annonce/{annonce_id}/status', name: 'change_status_annonce_entreprise', methods: ['POST'])]
    public function changeEntrepriseAnnonceStatus(Request $request, EntityManagerInterface $entityManager, int $entreprise_id, int $annonce_id, JobListingRepository $jobListingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        $entreprise = $entityManager->getRepository(EntrepriseProfile::class)->find($entreprise_id);
        $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);

        if (!$annonce) {
            $this->addFlash('error', 'Annonce introuvable.');
            return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
        }

        if ($status && in_array($status, JobListing::getArrayStatuses())) {
            $annonce->setStatus($status);
            $entityManager->flush();
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_entreprises_annonces', ['id' => $entreprise_id]);
    }

    #[Route('/entreprises/{entreprise_id}/annonces/{annonce_id}/view', name: 'app_dashboard_moderateur_entreprises_annonces_view')]
    public function entreprisesAnnoncesView(int $entreprise_id, int $annonce_id, EntrepriseProfileRepository $entrepriseProfileRepository, JobListingRepository $jobListingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $entreprise = $entrepriseProfileRepository->find($entreprise_id);
        $annonce = $jobListingRepository->findOneBy(['id' => $annonce_id, 'entreprise' => $entreprise]);

        if (!$entreprise || !$annonce) {
            throw $this->createNotFoundException('L\'entreprise ou l\'annonce n\'a pas été trouvée');
        }

        return $this->render('dashboard/moderateur/entreprises_annonces_view.html.twig', [
            'entreprise' => $entreprise,
            'annonce' => $annonce,
        ]);
    }

    #[Route('/candidats', name: 'app_dashboard_moderateur_candidats')]
    public function candidats(Request $request, PaginatorInterface $paginatorInterface, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Formulaire de recherche candidat */
        $form = $this->createForm(ModerateurCandidatSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllCandidat();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            $titre = $form->get('titre')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->searchCandidat($nom, $titre, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/annonce/_annonces.html.twig', [
                        'annonces' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/candidat/index.html.twig', [
            'candidats' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/candidat/{uid}/status', name: 'change_status_candidat', methods: ['POST'])]
    public function changeCandidatStatus(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidateProfile): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, ['PENDING', 'BANNISHED', 'VALID', 'FEATURED', 'RESERVED'])) {
            $candidateProfile->setStatus($status);
            $entityManager->flush();
            if($status === CandidateProfile::STATUS_VALID){
                /** On envoi un mail */
                $this->mailerService->send(
                    $candidateProfile->getCandidat()->getEmail(),
                    "Statut de votre profil sur Olona Talents",
                    "validate_profile.html.twig",
                    [
                        'user' => $candidateProfile->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

                /** Notification candidat */
                $notification = $this->notificationManager->init();
                $notification->setDestinataire($candidateProfile->getCandidat());
                $notification->setContenu("Votre profil a été validé et apparaitra sur la liste des talents");
                $notification->setTitre("Statut de votre profil sur Olona Talents.");
                $notification->setStatus(Notification::TYPE_PROFIL);
                $this->em->persist($notification);
                $this->em->flush();

            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidats');
    }

    #[Route('/candidat/{uid}/certification', name: 'change_status_certification_candidat', methods: ['POST'])]
    public function changeCertificationCandidatStatus(Request $request, EntityManagerInterface $entityManager, CandidateProfile $candidateProfile): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, ['OUI', 'NON'])) {
            $candidateProfile->setStatus($status);
            $entityManager->flush();
            if($status === CandidateProfile::STATUS_VALID){
                /** On envoi un mail */
                $this->mailerService->send(
                    $candidateProfile->getCandidat()->getEmail(),
                    "Statut de votre profil sur Olona Talents",
                    "validate_profile.html.twig",
                    [
                        'user' => $candidateProfile->getCandidat(),
                        'dashboard_url' => $this->urlGenerator->generate('app_connect', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]
                );

            }
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidats');
    }


    #[Route('/candidats/{id}', name: 'app_dashboard_moderateur_candidat_view')]
    public function viewCandidat(CandidateProfile $candidat): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/candidat_view.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/candidats/{id}/applications', name: 'app_dashboard_moderateur_candidat_applications')]
    public function candidatApplications(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id]);

        return $this->render('dashboard/moderateur/candidat_applications.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/status/application/{id}', name: 'change_status_application', methods: ['POST'])]
    public function changeApplicationStatus(Request $request, EntityManagerInterface $entityManager, Applications $application): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, Applications::getArrayStatuses())) {
            $application->setStatus($status);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');

            /** Envoi mail candidat */
            $this->mailerService->send(
                $application->getCandidat()->getCandidat()->getEmail(),
                "Statut de votre candidature sur Olona Talents",
                "candidat/status_candidature.html.twig",
                [
                    'user' => $application->getCandidat()->getCandidat(),
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_candidat_annonces', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Notification candidat */
            $notification = $this->notificationManager->init();
            $notification->setDestinataire($application->getCandidat()->getCandidat());
            $notification->setContenu("Votre candidature sur l'annonce " . $application->getAnnonce()->getTitre() . " a été validé et apparaitra sur la liste des talents");
            $notification->setTitre("Statut de votre candidature sur Olona Talents.");
            $notification->setStatus(Notification::TYPE_ANNONCE);
            $this->em->persist($notification);
            $this->em->flush();

            /** Envoi mail entreprise */
            $this->mailerService->send(
                $application->getAnnonce()->getEntreprise()->getEntreprise()->getEmail(),
                "Une candidature a été déposée sur votre annonce sur Olona Talents",
                "entreprise/status_candidature.html.twig",
                [
                    'user' => $application->getAnnonce()->getEntreprise()->getEntreprise(),
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_entreprise_candidatures', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

            /** Notification candidat */
            $notification1 = $this->notificationManager->init();
            $notification1->setDestinataire( $application->getAnnonce()->getEntreprise()->getEntreprise());
            $notification1->setContenu("Une candidature a été déposée sur votre annonce " . $application->getAnnonce()->getTitre() . ".");
            $notification1->setTitre("Une candidature a été déposée sur votre annonce.");
            $notification1->setStatus(Notification::TYPE_PROFIL);
            $this->em->persist($notification1);
            $this->em->flush();

            /** Envoi mail moderateurs */
            $this->mailerService->sendMultiple(
                $this->moderateurManager->getModerateurEmails(),
                "Une nouvelle candidature a été déposée pour une annonce sur Olona Talents",
                "moderateur/status_candidature.html.twig",
                [
                    'details_annonce' => $application->getAnnonce(),
                    'dashboard_url' => $this->urlGenerator->generate('app_dashboard_moderateur_candidature_view', ['id' => $application->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]
            );

        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidat_applications', ['id' => $application->getCandidat()->getId()]);
    }

    #[Route('/candidats/{id}/applications/en-attente', name: 'app_dashboard_moderateur_candidat_applications_en_attente')]
    public function candidatApplicationsEnAttente(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'PENDING']);

        return $this->render('dashboard/moderateur/candidat_applications_en_attente.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/applications/acceptees', name: 'app_dashboard_moderateur_candidat_applications_acceptees')]
    public function candidatApplicationsAcceptees(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'ACCEPTED']);

        return $this->render('dashboard/moderateur/candidat_applications_acceptees.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/applications/refusees', name: 'app_dashboard_moderateur_candidat_applications_refusees')]
    public function candidatApplicationsRefusees(int $id, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $applications = $applicationsRepository->findBy(['candidat' => $id, 'status' => 'REFUSED']);

        return $this->render('dashboard/moderateur/candidat_applications_refusees.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/candidats/{id}/competences', name: 'app_dashboard_moderateur_candidat_competences')]
    public function candidatCompetences(int $id, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $candidat = $candidateProfileRepository->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat introuvable');
        }

        return $this->render('dashboard/moderateur/candidat_competences.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/candidats/{id}/experiences', name: 'app_dashboard_moderateur_candidat_experiences')]
    public function candidatExperiences(int $id, CandidateProfileRepository $candidateProfileRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $candidat = $candidateProfileRepository->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat introuvable');
        }

        $experiences = $candidat->getExperiences();

        return $this->render('dashboard/moderateur/candidat_experiences.html.twig', [
            'candidat' => $candidat,
            'experiences' => $experiences,
        ]);
    }

    #[Route('/mettings', name: 'app_dashboard_moderateur_mettings')]
    public function mettings(MettingRepository $mettingRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $mettings = $mettingRepository->findAll();
        return $this->render('dashboard/moderateur/mettings.html.twig', compact('mettings'));
    }

    #[Route('/metting/show/{id}', name: 'app_dashboard_moderateur_metting_show', methods: ['GET'])]
    public function show(Metting $metting): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        return $this->render('dashboard/moderateur/mettings_show.html.twig', compact('metting'));
    }

    #[Route('/metting/new', name: 'app_dashboard_moderateur_metting_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $metting = new Metting();
        $form = $this->createForm(MettingType::class, $metting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($metting);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_moderateur_mettings');
        }

        return $this->render('dashboard/moderateur/mettings_new.html.twig', [
            'metting' => $metting,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/metting/{id}/edit', name: 'app_dashboard_moderateur_metting_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Metting $metting, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $form = $this->createForm(MettingType::class, $metting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_moderateur_mettings');
        }

        return $this->renderForm('dashboard/moderateur/mettings_edit.html.twig', [
            'metting' => $metting,
            'form' => $form,
        ]);
    }

    #[Route('/metting/{id}', name: 'app_dashboard_moderateur_metting_delete', methods: ['POST'])]
    public function delete(Request $request, Metting $metting, EntityManagerInterface $entityManager): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        if ($this->isCsrfTokenValid('delete' . $metting->getId(), $request->request->get('_token'))) {
            $entityManager->remove($metting);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_moderateur_mettings');
    }


    #[Route('/candidatures', name: 'app_dashboard_moderateur_candidatures')]
    public function candidatures(Request $request, PaginatorInterface $paginatorInterface, ApplicationsRepository $applicationsRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $form = $this->createForm(ModerateurCandidatureSearchType::class);
        $form->handleRequest($request);
        $data = $this->moderateurManager->findAllCandidatures();
        if ($form->isSubmitted() && $form->isValid()) {
            $titre = $form->get('titre')->getData();
            $entreprise = $form->get('entreprise')->getData();
            $candidat = $form->get('candidat')->getData();
            $status = $form->get('status')->getData();
            $data = $this->moderateurManager->findAllCandidatures($titre, $entreprise, $candidat, $status);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/candidature/_candidatures.html.twig', [
                        'candidatures' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }
        
        return $this->render('dashboard/moderateur/candidature/index.html.twig', [
            'candidatures' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/candidature/{id}', name: 'app_dashboard_moderateur_candidature_view')]
    public function candidature(Request $request, Applications $applications): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }
        
        return $this->render('dashboard/moderateur/candidature/view.html.twig', [
            'application' => $applications,
        ]);
    }

    #[Route('/candidature/{id}/status', name: 'app_dashboard_moderateur_candidature_status')]
    public function statusCandidature(Request $request, Applications $applications, NotificationRepository $notificationRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $status = $request->request->get('status');
        if ($status && in_array($status, Applications::getArrayStatuses())) {
            $applications->setStatus($status);
            $this->em->persist($applications);
            $this->em->flush();
            /** Envoi mail */

            $this->addFlash('success', 'Le statut a été mis à jour avec succès.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_dashboard_moderateur_candidat_applications', ['id' => $applications->getCandidat()->getId()]);
        
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'sectors' => $notificationRepository->findAll(),
        ]);
    }

    #[Route('/notifications', name: 'app_dashboard_moderateur_notifications')]
    public function notifications(Request $request, NotificationRepository $notificationRepository): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }
        
        return $this->render('dashboard/moderateur/notifications.html.twig', [
            'sectors' => $notificationRepository->findAll(),
        ]);
    }
}
