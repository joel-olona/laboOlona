<?php

namespace App\Controller\Dashboard\Moderateur;

use App\Data\ImportData;
use App\Service\WooCommerce;
use App\Entity\AffiliateTool;
use App\Form\Import\ImportType;
use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Manager\AffiliateToolManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Moderateur\AffiliateToolType;
use App\Form\Search\Secteur\ToolSearchType;
use App\Repository\AffiliateToolRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/affiliate')]
class AffiliateToolController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private AffiliateToolRepository $affiliateToolRepository,
        private AffiliateToolManager $affiliateToolManager,
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

    #[Route('/tools', name: 'app_dashboard_moderateur_affiliate_tool')]
    public function index(Request $request, PaginatorInterface $paginatorInterface): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        $form = $this->createForm(ToolSearchType::class);
        $form->handleRequest($request);
        $data = $this->affiliateToolManager->findAllAITools();
        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $form->get('nom')->getData();
            // $type = $form->get('type')->getData();
            $data = $this->affiliateToolManager->findSearchTools($nom);
            if ($request->isXmlHttpRequest()) {
                // Si c'est une requête AJAX, renvoyer une réponse JSON ou un fragment HTML
                return new JsonResponse([
                    'content' => $this->renderView('dashboard/moderateur/affiliate_tool/_aitools.html.twig', [
                        'aiTools' => $paginatorInterface->paginate(
                            $data,
                            $request->query->getInt('page', 1),
                            10
                        ),
                        'result' => $data
                    ])
                ]);
            }
        }

        return $this->render('dashboard/moderateur/affiliate_tool/index.html.twig', [
            'aiTools' => $paginatorInterface->paginate(
                $data,
                $request->query->getInt('page', 1),
                10
            ),
            'result' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tool/new', name: 'app_dashboard_moderateur_new_affiliate_tool')]
    public function newTool(Request $request): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** Initialiser une instance de AffiliateTool */
        $tool = $this->affiliateToolManager->init();
        $form = $this->createForm(AffiliateToolType::class, $tool);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder AffiliateTool */
            $tool = $this->affiliateToolManager->saveForm($form);
            $this->addFlash('success', 'AffiliateTool sauvegardé');

            return $this->redirectToRoute('app_dashboard_moderateur_affiliate_tool', []);
        }

        return $this->render('dashboard/moderateur/affiliate_tool/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Créer',
        ]);

    }

    #[Route('/tool/{slug}/edit', name: 'app_dashboard_moderateur_edit_affiliate_tool')]
    public function editTool(Request $request, AffiliateTool $tool): Response
    {
        $redirection = $this->checkModerateur();
        if ($redirection !== null) {
            return $redirection; 
        }

        /** @var AffiliateTool $tool qui vient de {slug} */
        $form = $this->createForm(AffiliateToolType::class, $tool);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** Sauvegarder AffiliateTool */
            $tool = $this->affiliateToolManager->saveForm($form);
            $this->addFlash('success', 'AffiliateTool mis à jour');

            return $this->redirectToRoute('app_dashboard_moderateur_affiliate_tool', []);
        }

        return $this->render('dashboard/moderateur/affiliate_tool/new_edit.html.twig', [
            'form' => $form->createView(),
            'button_label' => 'Mettre à jour',
        ]);

    }
    #[Route('/tool/import', name: 'app_dashboard_moderateur_import_affiliate_tool')]
    public function importCsvAction(
        Request $request, 
        EntityManagerInterface $entityManager, 
        SluggerInterface $sluggerInterface,
        WooCommerce $woocommerce
    )
    {
        $importType = new ImportData();
        $formImport = $this->createForm(ImportType::class, $importType);
        $formImport->handleRequest($request);
        $products = $woocommerce->importProduct($importType);
            
            foreach ($products as $product) {

                $entity = $this->affiliateToolRepository->findOneBy(['slug' => $product['slug']]);

                if(!$entity instanceof AffiliateTool){
                    $entity = new AffiliateTool();
                }

                $entity->setNom($product['name']);
                $entity->setSlug($sluggerInterface->slug(strtolower($product['name'])));
                $entity->setType($product['status']);
                $entity->setLienAffiliation($product['external_url']);
                $entity->setImage($product['images'][0]->src);
                $entity->setDescription($product['short_description']);
                $entity->setCommission(1.80);

                // foreach ($product['categories'] as $category) {

                //     $aIcategory = $aIcategoryRepository->findOneBy(['slug' => $category->slug]);

                //     if(!$aIcategory instanceof AIcategory){
                //         $aIcategory = new AIcategory();
                //     }

                //     $aIcategory->setName($category->name);
                //     $aIcategory->setSlug($category->slug);

                //     $entityManager->persist($aIcategory);
                //     $entity->addAIcategory($aIcategory);
                // }

                $entityManager->persist($entity);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Les produits sont bien importés');

        return $this->render('dashboard/moderateur/affiliate_tool/import.html.twig', [
            'formImport' => $formImport->createView(),
            'products' => $products
        ]);
    }
}
