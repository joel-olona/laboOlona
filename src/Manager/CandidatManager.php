<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Service\User\UserService;
use App\Entity\Entreprise\JobListing;
use App\Entity\Candidate\Applications;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidateProfileRepository;
use App\Repository\EntrepriseProfileRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Candidate\ApplicationsRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class CandidatManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private JobListingRepository $jobListingRepository,
        private ApplicationsRepository $applicationsRepository,
        private UserService $userService
    ){}

    
    public function annoncesCandidatDefaut(CandidateProfile $candidat): array
    {
        return array_slice(array_merge(
            $this->getPostingsByCandidatSectors($candidat),
            $this->getPostingsByCandidatSkills($candidat),
            $this->getPostingsByCandidatLocalisation($candidat)
        ), 0, 6);
    }
    
    public function findExpertAnnouncements(CandidateProfile $candidat): array
    {
        return array_slice(array_merge(
            $this->getPostingsByCandidatSkills($candidat),
            $this->getPostingsByCandidatLocalisation($candidat),
            $this->getPostingsByCandidatSectors($candidat)
        ), 0, 6);
    }
    
    public function getPostingsByCandidatSectors(CandidateProfile $candidat): array
    {
        $annonces = [];
        $sectors = $candidat->getSecteurs();
        foreach ($sectors as $sector) {
            $sectorEntreprises = $sector->getJobListings();
            foreach ($sectorEntreprises as $posting) {
                if($posting->getStatus() === JobListing::STATUS_PUBLISHED || $posting->getStatus() === JobListing::STATUS_FEATURED  ){
                    $annonces[] = $posting;
                }
            }
        }

        return $annonces;
    }
    
    public function getPostingsByCandidatLocalisation(CandidateProfile $candidat): array
    {
        $annonces = [];

        $entreprise = $this->entrepriseProfileRepository->findBy([
            'localisation' => $candidat->getLocalisation()
        ]);

        foreach ($entreprise as $company) {
            $companyPostings = $company->getJobListings();
            foreach ($companyPostings as $posting) {
                if($posting->getStatus() === JobListing::STATUS_PUBLISHED || $posting->getStatus() === JobListing::STATUS_FEATURED  ){
                    $annonces[] = $posting;
                }
            }
        }

        return $annonces;
    }
    
    public function getPostingsByCandidatSkills(CandidateProfile $candidat): array
    {
        $annonces = [];
        $skills = $candidat->getCompetences();
        foreach ($skills as $skill) {
            $skillPostings = $skill->getJobListings();
            foreach ($skillPostings as $posting) {
                if($posting->getStatus() === JobListing::STATUS_PUBLISHED || $posting->getStatus() === JobListing::STATUS_FEATURED  ){
                    $annonces[] = $posting;
                }
            }
        }

        return $annonces;
    }
    
    public function getAll(): array
    {
        return $this->candidateProfileRepository->findAll();
    }

    public function searchAnnonce(?string $titre = null, ?string $lieu = null, ?string $typeContrat = null, ?string $competences = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if($titre == null && $lieu == null && $typeContrat == null && $competences == null){
            return $this->jobListingRepository->findAllJobListingPublished();
        }

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($typeContrat) ) {
            $conditions[] = '(j.typeContrat LIKE :typeContrat )';
            $parameters['typeContrat'] = '%' . $typeContrat . '%';
        }

        if (!empty($competences)) {
            $conditions[] = '(c.nom LIKE :competences )';
            $parameters['competences'] = '%' . $competences . '%';
        }

        if (!empty($lieu)) {
            $conditions[] = '(j.lieu LIKE :lieu )';
            $parameters['lieu'] = '%' . $lieu . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.competences', 'c')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

    public function getPendingApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_PENDING
        ]);
    }

    public function getAcceptedApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_ACCEPTED
        ]);
    }

    public function getRefusedApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_REJECTED
        ]);
    }

    public function getArchivedApplications(CandidateProfile $candidat): array
    {
        return $this->applicationsRepository->findBy([
            'candidat' => $candidat,
            'status' => Applications::STATUS_ARCHIVED
        ]);
    }

}
