<?php

namespace App\Controller\Ajax;

use App\Entity\Secteur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Mapping as ORM;

class ModerateurController extends AbstractController
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;
    
    #[Route('/ajax/remove/{id}/sector', name: 'ajax_remove_sector')]
    public function remove(Secteur $secteur): Response
    {
        return $this->json([], 200, []);
    }

    #[Route('/ajax/edit/{id}/sector', name: 'ajax_edit_sector')]
    public function edit(Secteur $secteur): Response
    {
        return $this->json([], 200, []);
    }

    /*#[Route('/ajax/status/annonce/{id}', name: 'ajax_change_status_annonce')]
    public function annonce(Secteur $secteur): Response
    {
        return $this->json([], 200, []);
    }*/

     

}
