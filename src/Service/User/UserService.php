<?php

namespace App\Service\User;

use App\Entity\ProfilCandidat;
use App\Entity\ProfilEntreprise;
use DateTime;
use App\Entity\User;
use Symfony\Component\Form\Form;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class UserService
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
    ){
    }

    public function getCurrentUser()
    {
        return $this->security->getUser();
    }

    public function init()
    {
        $user = new User();
        $user->setDateInscription(new DateTime());
        
        return $user;
    }

    public function initEntreprise(User $user)
    {
        $profilEntreprise = new ProfilEntreprise();
        $profilEntreprise->setEntrepriseId($user);
        
        return $profilEntreprise;
    }

    public function initCandidat(User $user)
    {
        $profilCandidat = new ProfilCandidat();
        $profilCandidat->setCandidatId($user);
        
        return $profilCandidat;
    }

    public function save(User $user)
    {
		$this->em->persist($user);
        $this->em->flush();
    }

    public function saveEntreprise(ProfilEntreprise $profilEntreprise)
    {
		$this->em->persist($profilEntreprise);
        $this->em->flush();
    }

    public function saveCandidat(ProfilCandidat $profilCandidat)
    {
		$this->em->persist($profilCandidat);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $user = $form->getData();
        $this->save($user);

        return $user;

    }

    public function saveProfilEntreprise(Form $form)
    {
        $profilEntreprise = $form->getData();
        $this->saveEntreprise($profilEntreprise);

        return $profilEntreprise;

    }

    public function saveProfilCandidat(Form $form)
    {
        $profilCandidat = $form->getData();
        $this->saveCandidat($profilCandidat);

        return $profilCandidat;

    }
}