<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function createCompany($user)
    {
        $company = new EntrepriseProfile();
        $company->setEntreprise($user);

        return $company;
    }

    public function createCandidat($user)
    {
        $candidate = new CandidateProfile();
        $candidate->setCandidat($user);

        return $candidate;
    }

    public function saveCandidate(CandidateProfile $candidate)
    {
		$this->em->persist($candidate);
        $this->em->flush();
    }

    public function saveCompany(EntrepriseProfile $company)
    {
		$this->em->persist($company);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $profile = $form->getData();
        if($profile instanceof EntrepriseProfile){
            $this->saveCompany($profile);
        }
        if($profile instanceof CandidateProfile){
            $this->saveCandidate($profile);
        }

        return $profile;

    }
}