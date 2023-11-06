<?php

namespace App\Controller\Ajax;

use App\Entity\Secteur;
use App\Repository\CandidateProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CandidatController extends AbstractController
{
    #[Route('/ajax/candidat', name: 'app_ajax_candidat')]
    public function index(
        Request $request,
        CandidateProfileRepository $candidateProfileRepository
    ): Response
    {
        $offset = $request->query->get('offset', 0);

        return $this->json([
            'html' => $this->renderView('components/scroll_candidat_component.html.twig', [
                'candidats' => $candidateProfileRepository->findTopExperts('', 10, $offset),
            ], []),
        ], 200, [], []);

        // return $this->json([
        //     'data' => $expertRepository->findTopExperts('', 10, $offset),
        // ], 200, [], ['groups' => 'identity']);
    }

}
