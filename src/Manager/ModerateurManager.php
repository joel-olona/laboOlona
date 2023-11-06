<?php

namespace App\Manager;

use App\Entity\Moderateur\TypeContrat;
use DateTime;
use App\Entity\Secteur;
use App\Repository\CandidateProfileRepository;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\EntrepriseProfileRepository;
use App\Repository\Moderateur\TypeContratRepository;
use App\Repository\SecteurRepository;
use Symfony\Component\Form\Form;
use App\Service\User\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\SluggerInterface;

class ModerateurManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $sluggerInterface,
        private RequestStack $requestStack,
        private SecteurRepository $secteurRepository,
        private TypeContratRepository $typeContratRepository,
        private JobListingRepository $jobListingRepository,
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private CandidateProfileRepository $candidateProfileRepository,
        private UserService $userService
    ){}

    public function initSector(): Secteur
    {
        return new Secteur();
    }

    public function saveSector(Secteur $secteur): void
    {
		$this->em->persist($secteur);
        $this->em->flush();
    }

    public function saveSectorForm(Form $form)
    {
        $secteur = $form->getData();
        $secteur->setSlug($this->sluggerInterface->slug(strtolower($secteur->getNom())));
        $this->saveSector($secteur);

        return $secteur;

    }

    public function deleteSector(Secteur $secteur): void
    {
		$this->em->remove($secteur);
        $this->em->flush();
    }

    public function initTypeContrat(): TypeContrat
    {
        return new TypeContrat();
    }

    public function saveTypeContrat(TypeContrat $typeContrat): void
    {
		$this->em->persist($typeContrat);
        $this->em->flush();
    }

    public function saveTypeContratForm(Form $form)
    {
        $typeContrat = $form->getData();
        $typeContrat->setSlug($this->sluggerInterface->slug(strtolower($typeContrat->getNom())));
        $this->saveTypeContrat($typeContrat);

        return $typeContrat;

    }

    public function deleteTypeContrat(TypeContrat $typeContrat): void
    {
		$this->em->remove($typeContrat);
        $this->em->flush();
    }

    public function findAllListingJob(): array
    {
        return $this->jobListingRepository->findAll();
    }

    public function findAllEntreprise(): array
    {
        return $this->entrepriseProfileRepository->findAll();
    }

    public function findAllCandidat(): array
    {
        return $this->candidateProfileRepository->findAll();
    }

    public function getSecteurChoice(): array
    {
        $choices = [];
        $secteurs =  $this->secteurRepository->findAll();
        foreach($secteurs as $secteur){
            $choices[$secteur->getNom()] = $secteur->getSlug();
        }
        
        return $choices;
    }

    public function searchSecteur(string $query = null): array
    {
        if (empty($query)) {
            return $this->secteurRepository->findAll();
        }

        $qb = $this->em->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];
        $conditions = [];

        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(s.nom LIKE :query' . $key .')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        $qb->select('s')
            ->from('App\Entity\Secteur', 's')
            ->where(implode(' OR ', $conditions))
            ->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    public function searchTypeContrat(string $query = null): array
    {
        if (empty($query)) {
            return $this->typeContratRepository->findAll();
        }

        $qb = $this->em->createQueryBuilder();

        $keywords = array_filter(explode(' ', $query));
        $parameters = [];
        $conditions = [];

        foreach ($keywords as $key => $keyword) {
            $conditions[] = '(t.nom LIKE :query' . $key .')';
            $parameters['query' . $key] = '%' . $keyword . '%';
        }

        $qb->select('t')
            ->from('App\Entity\Moderateur\TypeContrat', 't')
            ->where(implode(' OR ', $conditions))
            ->setParameters($parameters);

        return $qb->getQuery()->getResult();
    }

    public function searchAnnonce(?string $titre = null, ?string $entreprise = null, ?string $status = null): array
    {
        $qb = $this->em->createQueryBuilder();

        $parameters = [];
        $conditions = [];

        if (!empty($titre)) {
            $conditions[] = '(j.titre LIKE :titre )';
            $parameters['titre'] = '%' . $titre . '%';
        }

        if (!empty($entreprise)) {
            $conditions[] = '(e.nom LIKE :entreprise )';
            $parameters['entreprise'] = '%' . $entreprise . '%';
        }

        if (!empty($status)) {
            $conditions[] = '(j.status LIKE :status )';
            $parameters['status'] = '%' . $status . '%';
        }

        $qb->select('j')
            ->from('App\Entity\Entreprise\JobListing', 'j')
            ->leftJoin('j.entreprise', 'e')
            ->where(implode(' AND ', $conditions))
            ->setParameters($parameters);
        
        return $qb->getQuery()->getResult();
    }

}
