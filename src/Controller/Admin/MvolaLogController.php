<?php 

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MvolaLogController extends AbstractController
{
    #[Route('/admin/logs/mvola', name: 'admin_logs_mvola')]
    public function index(Request $request): Response
    {
        // Récupérer la date depuis les paramètres GET ou utiliser la date du jour
        $dateParam = $request->query->get('date');
        $date = \DateTime::createFromFormat('Y-m-d', $dateParam) ?: new \DateTime('today');

        $logFilename = 'mvola-' . $date->format('Y-m-d') . '.log';
        $logPath = $this->getParameter('kernel.logs_dir') . '/' . $logFilename;

        if (!file_exists($logPath)) {
            return new Response("Fichier de log introuvable pour la date " . $date->format('Y-m-d'), 404, [
                'Content-Type' => 'text/plain',
            ]);
        }

        return new Response(file_get_contents($logPath), 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
