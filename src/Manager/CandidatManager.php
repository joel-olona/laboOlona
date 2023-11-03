<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use DateTime;
use App\Entity\Secteur;
use Symfony\Component\Form\Form;
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
        private UserService $userService
    ){}

    
    public function annoncesCandidat(CandidateProfile $candidat): array
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
            $this->getPostingsByCandidatSectors($candidat),
            $this->getPostingsByCandidatSkills($candidat),
            $this->getPostingsByCandidatLocalisation($candidat)
        ), 0, 6);
    }
    
    public function getPostingsByCandidatSectors(CandidateProfile $candidat): array
    {
        $postings = [];
        // $sectors = $candidat->getSecteurs();
        // foreach ($sectors as $sector) {
        //     $sectorEntreprises = $sector->getEntreprise();
        //     foreach ($sectorEntreprises as $entreprise) {
        //         $annonce = $entreprise->getJobListings();
        //         if($posting->getStatus() === Posting::STATUS_PUBLISHED){
        //             $postings[] = $posting;
        //         }
        //     }
        // }

        return $postings;
    }
    
    public function getPostingsByCandidatLocalisation(CandidateProfile $candidat): array
    {
        $postings = [];
        // $companies = $this->companyRepository->findBy([
        //     'country' => $candidat->getCountry()
        // ]);
        // foreach ($companies as $company) {
        //     $companyPostings = $company->getPostings();
        //     foreach ($companyPostings as $posting) {
        //         if($posting->getStatus() === Posting::STATUS_PUBLISHED){
        //             $postings[] = $posting;
        //         }
        //     }
        // }

        return $postings;
    }
    
    public function getPostingsByCandidatSkills(CandidateProfile $candidat): array
    {
        $postings = [];
        // $skills = $candidat->getIdentity()->getTechnicalSkills();
        // foreach ($skills as $skill) {
        //     $skillPostings = $skill->getPostings();
        //     foreach ($skillPostings as $posting) {
        //         if($posting->getStatus() === Posting::STATUS_PUBLISHED){
        //             $postings[] = $posting;
        //         }
        //     }
        // }

        return $postings;
    }

}
