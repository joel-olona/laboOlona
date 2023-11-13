<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Repository\CandidateProfileRepository;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\EntrepriseProfileRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class EntrepriseManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private JobListingRepository $jobListingRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserService $userService
    ){}
    
    public function findValidCandidats(): array
    {
        return $this->candidateProfileRepository->findBy([
            'status' => CandidateProfile::STATUS_VALID
        ]);
    }

    public function findAllAnnonces(?string $titre = null, ?string $status = null, ?string $typeContrat = null, ?string $salaire = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];
        $conditions = [];

        if($titre == null && $status == null && $typeContrat == null && $salaire == null){
            return $this->jobListingRepository->findBy(
                ['entreprise' => $user->getEntrepriseProfile()],
                ['id' => 'DESC']
            );
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($typeContrat) ) {
            $conditions[] = '(j.typeContrat LIKE :typeContrat )';
            $parameters['typeContrat'] = '%' . $typeContrat . '%';
        }

        if (!empty($salaire)) {
            $conditions[] = '(j.salaire LIKE :salaire )';
            $parameters['salaire'] = '%' . $salaire . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->orderBy('j.id', 'DESC')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function findAllCandidats(?string $titre = null, ?string $status = null, ?string $typeContrat = null, ?string $salaire = null): array
    {
        /** @var User $user */
        $user = $this->userService->getCurrentUser();
        $qb = $this->em->createQueryBuilder();

        $parameters = [
            'entreprise' => $user->getEntrepriseProfile(),
        ];
        $conditions = [];

        if($titre == null && $status == null && $typeContrat == null && $salaire == null){
            return $this->jobListingRepository->findBy([
                'entreprise' => $user->getEntrepriseProfile(),
            ]);
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($typeContrat) ) {
            $conditions[] = '(j.typeContrat LIKE :typeContrat )';
            $parameters['typeContrat'] = '%' . $typeContrat . '%';
        }

        if (!empty($salaire)) {
            $conditions[] = '(j.salaire LIKE :salaire )';
            $parameters['salaire'] = '%' . $salaire . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->where(implode(' AND ', $conditions))
            ->andWhere('j.entreprise = :entreprise')
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }
}
