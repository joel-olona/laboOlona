<?php

namespace App\Controller\Dashboard\Moderateur\Profile;

use App\Entity\TemplateEmail;
use App\Service\User\UserService;
use App\Manager\ModerateurManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Data\Profile\RecrutementSearchData;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CandidateProfileRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Moderateur\Profile\RecrutementSearchFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/dashboard/moderateur/profile/recrutement')]
class RecrutementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ModerateurManager $moderateurManager,
        private UserService $userService,
        private CandidateProfileRepository $candidateProfileRepository,
    ){}
    
    #[Route('/', name: 'app_dashboard_moderateur_profile_recrutement')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('MODERATEUR_ACCESS', null, 'Vous n\'avez pas les permissions nécessaires pour accéder à cette partie du site. Cette section est réservée aux administrateurs uniquement. Veuillez contacter l\'administrateur si vous pensez qu\'il s\'agit d\'une erreur.');
        $data = new RecrutementSearchData();
        $data->page = $request->get('page', 1);
        $form = $this->createForm(RecrutementSearchFormType::class, $data);
        $form->handleRequest($request);
        $candidats = $this->candidateProfileRepository->findRecrutSearch($data);

        return $this->render('dashboard/moderateur/profile/recrutement/index.html.twig', [
            'candidats' => $candidats,
            'form' => $form->createView(),
            'templateEmails' => $this->em->getRepository(TemplateEmail::class)->findAll(),
        ]);
    }
}
