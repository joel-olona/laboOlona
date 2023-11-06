<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use App\Repository\EntrepriseProfileRepository;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class CandidatManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
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

}
