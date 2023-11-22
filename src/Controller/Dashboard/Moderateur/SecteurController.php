<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Entity\Secteur;
use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use App\Form\Moderateur\SecteurType;
use App\Repository\SecteurRepository;
use App\Service\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Search\Secteur\SecteurSearchType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur')]
class SecteurController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private MailerService $mailerService,
        private ModerateurManager $moderateurManager,
    ) {
    }

    private function checkModerateur()
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $moderateur = $user->getModerateurProfile();
        if (!$moderateur instanceof ModerateurProfile){ 
            return $this->redirectToRoute('app_connect');
        }

        return null;
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
}
