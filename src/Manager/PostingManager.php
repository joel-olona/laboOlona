<?php

namespace App\Manager;

use DateTime;
use App\Entity\Expert;
use App\Entity\Company;
use App\Entity\Posting;
use App\Entity\Application;
use Symfony\Component\Uid\Uuid;
use App\Service\User\UserService;
use App\Repository\AccountRepository;
use App\Repository\ApplicationRepository;
use App\Repository\CompanyRepository;
use App\Repository\PostingRepository;
use App\Repository\IdentityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostingManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private AccountRepository $accountRepository,
        private RequestStack $requestStack,
        private IdentityRepository $identityRepository,
        private PostingRepository $postingRepository,
        private CompanyRepository $companyRepository,
        private ApplicationRepository $applicationRepository,
        private UserService $userService
    ){}

    public function init(Company $company): Posting
    {
        $posting = new Posting();
        $posting
            ->setCreatedAt(new DateTime())
            ->setJobId(new Uuid(Uuid::v1()))
            ->setCompany($company)
            ->setIsValid(false)
            ->setStatus(Posting::STATUS_DRAFT)
            ;

        return $posting;
    }
    
    public function splitPostingsByValidity(Company $company): array
    {
        $postings = $company->getPostings();

        $postingsOk = [];
        $postingsFail = [];

        foreach ($postings as $posting) {
            if ($posting->isIsValid()) {
                $postingsOk[] = $posting;
            } else {
                $postingsFail[] = $posting;
            }
        }

        return [$postingsOk, $postingsFail];
    }
    
    public function findExpertAnnouncements(Expert $expert): array
    {
        return array_slice(array_merge(
            $this->getPostingsByExpertSectors($expert),
            $this->getPostingsByExpertSkills($expert),
            $this->getPostingsByExpertLocalisation($expert)
        ), 0, 6);
    }
    
    public function getPostingsByExpertSectors(Expert $expert): array
    {
        $postings = [];
        $sectors = $expert->getSectors();
        foreach ($sectors as $sector) {
            $sectorPostings = $sector->getPostings();
            foreach ($sectorPostings as $posting) {
                if($posting->getStatus() === Posting::STATUS_PUBLISHED){
                    $postings[] = $posting;
                }
            }
        }

        return $postings;
    }
    
    public function getPostingsByExpertLocalisation(Expert $expert): array
    {
        $postings = [];
        $companies = $this->companyRepository->findBy([
            'country' => $expert->getCountry()
        ]);
        foreach ($companies as $company) {
            $companyPostings = $company->getPostings();
            foreach ($companyPostings as $posting) {
                if($posting->getStatus() === Posting::STATUS_PUBLISHED){
                    $postings[] = $posting;
                }
            }
        }

        return $postings;
    }
    
    public function getPostingsByExpertSkills(Expert $expert): array
    {
        $postings = [];
        $skills = $expert->getIdentity()->getTechnicalSkills();
        foreach ($skills as $skill) {
            $skillPostings = $skill->getPostings();
            foreach ($skillPostings as $posting) {
                if($posting->getStatus() === Posting::STATUS_PUBLISHED){
                    $postings[] = $posting;
                }
            }
        }

        return $postings;
    }
    
    public function postuler(Posting $posting): Application
    {
        $identity = $this->userService->getCurrentIdentity();
        $application = $this->applicationRepository->findOneBy([
            'identity' => $identity,
            'posting' => $posting,
        ]);

        if(!$application instanceof Application){
            $application = new Application();
            $application->setCreatedAt(new DateTime());
            $application->setPosting($posting);
            $application->setStatus(Application::STATUS_PENDING);
            $application->setIdentity($identity);
        }

        return $application;
    }
    
    public function saveApplication(Application $application): void
    {
		$this->em->persist($application);
        $this->em->flush();
    }
    
    public function allPosting(): array
    {
        $postings = [];
        foreach ($this->identityRepository->findSearch() as $identity) {
            $postings[] = $identity->getExpert();
        }
        return $postings;
    }
    
    public function allExpertPosting($expert): array
    {
        return array_merge(
            $this->getPostingsByExpertSectors($expert),
            $this->getPostingsByExpertSkills($expert),
            $this->getPostingsByExpertLocalisation($expert)
        );
    }
}
