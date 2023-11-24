<?php

namespace App\Controller\Dashboard\Moderateur;

use DateTime;
use App\Data\ImportData;
use App\Service\WooCommerce;
use App\Entity\AffiliateTool;
use App\Form\Import\ImportType;
use App\Entity\AffiliateTool\Tag;
use App\Entity\ModerateurProfile;
use App\Service\User\UserService;
use App\Manager\AffiliateToolManager;
use App\Entity\AffiliateTool\Category;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Moderateur\AffiliateToolType;
use App\Repository\AffiliateToolRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\AffiliateTool\TagRepository;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Search\AffiliateTool\ToolSearchType;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\AffiliateTool\CategoryRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/affiliate')]
class AffiliateToolController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $em,
        private AffiliateToolRepository $affiliateToolRepository,
        private AffiliateToolManager $affiliateToolManager,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
        private SluggerInterface $sluggerInterface,
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

    #[Route('/tool/{slug}/view', name: 'app_dashboard_moderateur_view_affiliate_tool')]
    public function viewTool(Request $request, AffiliateTool $tool): Response
    {
        $tools = $tool->getRelatedIds();
        foreach ($tools as $key => $value) {
            $relateds[] = $this->affiliateToolRepository->findOneBy(['customId' => $value]); 
        }
        return $this->render('dashboard/moderateur/affiliate_tool/view.html.twig', [
            'aiTool' => $tool,
            'relateds' => $relateds,
        ]);

    }

    #[Route('/tool/import', name: 'app_dashboard_moderateur_import_affiliate_tool')]
    public function importCsvAction(Request $request, WooCommerce $woocommerce )
    {
        $importType = new ImportData();
        $formImport = $this->createForm(ImportType::class, $importType);
        $formImport->handleRequest($request);
        $products = $this->affiliateToolManager->findAllAITools();

        if ($formImport->isSubmitted() && $formImport->isValid()) {
            $products = $woocommerce->importProduct($importType);
            
            foreach ($products as $product) {

                $entity = $this->affiliateToolRepository->findOneBy(['slug' => $product['slug']]);

                if(!$entity instanceof AffiliateTool){
                    $entity = new AffiliateTool();
                }

                $entity->setNom($product['name']);
                $entity->setDescription($product['description']);
                $entity->setLienAffiliation($product['external_url']);
                $entity->setCommission(0.90);
                $entity->setSlug($this->sluggerInterface->slug(strtolower($product['name'])));
                $entity->setType($product['status']);
                $entity->setImage($product['images'][0]->src);
                $entity->setCustomId($product['id']);
                $entity->setShortDescription($product['short_description']);
                $entity->setSlogan($product['slogan']);
                $entity->setPrix(number_format(floatval($product['price']), 2, '.', ''));
                $entity->setCreeLe(new DateTime($product['date_created']));
                $entity->setEditeLe(new DateTime());
                $entity->setRelatedIds($product['related_ids']);

                foreach ($product['categories'] as $category) {

                    $aIcategory = $this->categoryRepository->findOneBy(['slug' => $this->sluggerInterface->slug(strtolower($category->name))]);

                    if(!$aIcategory instanceof Category){
                        $aIcategory = new Category();
                    }

                    $aIcategory->setnom($category->name);
                    $aIcategory->setSlug($this->sluggerInterface->slug(strtolower($category->name)));

                    $this->em->persist($aIcategory);
                    $entity->addCategory($aIcategory);
                }

                foreach ($product['tags'] as $tag) {

                    $aItag = $this->tagRepository->findOneBy(['slug' => $this->sluggerInterface->slug(strtolower($tag->name))]);

                    if(!$aItag instanceof Tag){
                        $aItag = new Tag();
                    }

                    $aItag->setnom($tag->name);
                    $aItag->setSlug($this->sluggerInterface->slug(strtolower($tag->name)));

                    $this->em->persist($aItag);
                    $entity->addTag($aItag);
                }

                $this->em->persist($entity);
            }
            $this->em->flush();
            $this->addFlash('success', 'Les produits sont bien importés');

        }

        return $this->render('dashboard/moderateur/affiliate_tool/import.html.twig', [
            'formImport' => $formImport->createView(),
            'products' => $products
        ]);
    }
}
