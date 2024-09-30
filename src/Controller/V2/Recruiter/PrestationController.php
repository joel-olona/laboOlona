<?php

namespace App\Controller\V2\Recruiter;

use App\Entity\User;
use App\Entity\Prestation;
use App\Form\PrestationType;
use App\Service\FileUploader;
use App\Data\V2\PrestationData;
use App\Manager\ProfileManager;
use App\Service\User\UserService;
use Symfony\UX\Turbo\TurboBundle;
use App\Manager\PrestationManager;
use App\Entity\BusinessModel\Boost;
use App\Entity\BusinessModel\Credit;
use Doctrine\ORM\EntityManagerInterface;
use App\Manager\BusinessModel\CreditManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/v2/recruiter/prestation')]
class PrestationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProfileManager $profileManager,
        private FileUploader $fileUploader,
        private UserService $userService,
        private PrestationManager $prestationManager,
        private CreditManager $creditManager,
    ){}
    
    #[Route('/', name: 'app_v2_recruiter_prestation')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new PrestationData();
        $data->page = $request->get('page', 1);
        $data->entreprise = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/prestation/index.html.twig', [
            'prestations' => $this->em->getRepository(Prestation::class)->findSearch($data)
        ]);
    }
    
    #[Route('/create', name: 'app_v2_recruiter_create_prestation')]
    public function createPrestation(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        /** @var User $currentUser */
        $currentUser = $this->userService->getCurrentUser();
        /** @var Prestation $prestation */
        $prestation = $this->prestationManager->init();
        $prestation->setEntrepriseProfile($recruiter);
        $prestation->setContactEmail($currentUser->getEmail());
        $prestation->setContactTelephone($currentUser->getTelephone());
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => 'PRESTATION_RECRUITER']);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $prestation = $form->getData();
            $boost = $prestation->getBoost();
            if($boost instanceof Boost){
                $responseBoost = $this->creditManager->adjustCredits($currentUser, $boost->getCredit());
            }
            $message = 'Prestation créée avec succès';
            $success = true;
            $status = 'Succès';
        
            $creditAmount = $this->profileManager->getCreditAmount(Credit::ACTION_APPLY_PRESTATION_RECRUITER);
            $response = $this->creditManager->adjustCredits($this->userService->getCurrentUser(), $creditAmount);
            
            if (isset($response['error']) || isset($responseBoost['error'])) {
                $message = $response['error'];
                $success = false;
                $status = 'Echec';
            }

            if (isset($response['success']) && isset($responseBoost['success'])) {
                $this->prestationManager->saveForm($form);
            }

            if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
                $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
    
                return $this->render('v2/dashboard/recruiter/live.html.twig', [
                    'message' => $message,
                    'success' => $success,
                    'status' => $status,
                    'credit' => $currentUser->getCredit()->getTotal(),
                ]);
            }

            return $this->redirectToRoute('app_v2_recruiter_view_prestation', ['prestation' => $prestation->getId()]);
        }else {
            foreach ($form->getErrors(true) as $error) {
                dd($error->getMessage()); 
            }
        }

        return $this->render('v2/dashboard/recruiter/prestation/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/edit/{prestation}', name: 'app_v2_recruiter_edit_prestation')]
    public function editPrestation(Request $request, Prestation $prestation): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $form = $this->createForm(PrestationType::class, $prestation, ['boostType' => 'PRESTATION_RECRUITER']);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $prestation = $form->getData();
            $boost = $prestation->getBoost();
            if($boost instanceof Boost){
                $this->creditManager->adjustCredits($user, $boost->getCredit());
            }
            $this->prestationManager->saveForm($form);
            return $this->render('v2/dashboard/recruiter/prestation/edit.html.twig', [
                'prestation' => $prestation,
                'form' => $form->createView(),
                'prestation_description' => $prestation->getDescription()
            ]);
        }

        return $this->render('v2/dashboard/recruiter/prestation/edit.html.twig', [
            'prestation' => $prestation,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/view/{prestation}', name: 'app_v2_recruiter_view_prestation')]
    public function viewPrestation(Request $request, Prestation $prestation): Response
    {
        $this->denyAccessUnlessGranted('ENTREPRISE_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux recruteurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $recruiter = $this->userService->checkProfile();

        return $this->render('v2/dashboard/recruiter/prestation/view.html.twig', [
            'prestation' => $prestation,
        ]);
    }
    
    #[Route('/delete/{prestation}', name: 'app_v2_recruiter_delete_prestation')]
    public function removePrestation(Request $request, Prestation $prestation): Response
    {
        $prestationId = $prestation->getId();
        $message = "La prestation a bien été supprimée";
        $this->em->remove($prestation);
        $this->em->flush();
        if($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT){
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            return $this->render('v2/dashboard/recruiter/prestation/delete.html.twig', [
                'prestationId' => $prestationId,
                'message' => $message,
            ]);
        }
        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_v2_recruiter_prestation');

    }
}
