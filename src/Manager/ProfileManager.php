<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

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
        $candidate->setIsValid(false);
        $candidate->setStatus(CandidateProfile::STATUS_PENDING);
        $candidate->setUid(new Uuid(Uuid::v1()));

        return $candidate;
    }

    public function createModerateur($user)
    {
        $moderateur = new ModerateurProfile();
        $moderateur->setModerateur($user);
        $this->saveModerateur($moderateur);

        return $moderateur;
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

    public function saveModerateur(ModerateurProfile $moderateur)
    {
		$this->em->persist($moderateur);
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