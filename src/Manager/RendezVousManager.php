<?php

namespace App\Manager;

use DateTime;
use App\Entity\User;
use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Moderateur\Metting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class RendezVousManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private MettingRepository $mettingRepository,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function createRendezVous(
        ModerateurProfile $moderateur, 
        CandidateProfile $candidat, 
        EntrepriseProfile $entreprise
    ) : Metting
    {
        $rendezVous = new Metting();
        $rendezVous->setModerateur($moderateur);
        $rendezVous->setEntreprise($entreprise);
        $rendezVous->setCandidat($candidat);
        $rendezVous->setCreeLe(new DateTime());
        $rendezVous->setStatus(Metting::STATUS_PENDING);

        return $rendezVous;
    }

    public function save(Metting $rendezVous)
    {
		$this->em->persist($rendezVous);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        $this->save($form->getData());
    }

    public function getUserRole(User $user)
    {
        switch($user->getType()){
            case User::ACCOUNT_CANDIDAT :
                $role = $user->getCandidateProfile();
            break;

            case User::ACCOUNT_ENTREPRISE :
                $role = $user->getEntrepriseProfile();
            break;

            case User::ACCOUNT_MODERATEUR :
                $role = $user->getModerateurProfile();
            break;
        }

        return $role;
    }

    public function findMettingByRole($role) : array
    {
        if($role instanceof EntrepriseProfile){
            return $this->mettingRepository->findBy(
                ['entreprise' => $role],
                ['id' => 'DESC']
            );
        }

        if($role instanceof CandidateProfile){
            return $this->mettingRepository->findBy(
                ['candidat' => $role],
                ['id' => 'DESC']
            );
        }

        if($role instanceof ModerateurProfile){
            return $this->mettingRepository->findBy(
                ['moderateur' => $role],
                ['id' => 'DESC']
            );
        }
    }
}