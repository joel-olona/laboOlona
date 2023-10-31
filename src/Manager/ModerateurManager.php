<?php

namespace App\Manager;

use DateTime;
use App\Entity\Secteur;
use Symfony\Component\Form\Form;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class ModerateurManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private UserService $userService
    ){}

    public function initSector(): Secteur
    {
        return new Secteur();
    }

    public function saveSector(Secteur $secteur): void
    {
		$this->em->persist($secteur);
        $this->em->flush();
    }

    public function saveSectorForm(Form $form)
    {
        $secteur = $form->getData();
        $secteur->setSlug($this->sluggerInterface->slug(strtolower($secteur->getNom())));
        $this->saveSector($secteur);

        return $secteur;

    }


}
