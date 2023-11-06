<?php

namespace App\Twig\Components;

use App\Repository\Entreprise\JobListingRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('annonces_carousel_component')]
class AnnoncesCarouselComponent
{

    public function __construct(
        private JobListingRepository $jobListingRepository
    ){
    }

    public function getAnnonces(): array
    {
        return $this->jobListingRepository->findAll();
    }
}