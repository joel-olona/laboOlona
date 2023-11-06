<?php

namespace App\Manager;

use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
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
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserService $userService
    ){}
    
    public function findCandidats(): array
    {
        return [];
    }

}
