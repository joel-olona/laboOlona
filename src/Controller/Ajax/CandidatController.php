<?php

namespace App\Controller\Ajax;

use App\Entity\Secteur;
use App\Repository\AffiliateToolRepository;
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

    }

    #[Route('/ajax/ai-tools', name: 'app_ajax_aitools')]
    public function aiTools(
        Request $request,
        AffiliateToolRepository $affiliateToolRepository
    ): Response
    {
        $offset = $request->query->get('offset', 0);
        // $offset = $request->query->get('offset', 0);

        return $this->json([
            'html' => $this->renderView('ai_tool/_tools.html.twig', [
                'aiTools' => $affiliateToolRepository->findSearch('publish', 12, $offset),
            ], []),
        ], 200, [], []);

    }

}
