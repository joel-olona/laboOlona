<?php

namespace App\Manager;

use DateTime;
use App\Entity\User;
use Twig\Environment as Twig;
use App\Entity\CandidateProfile;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\Form;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use App\Entity\Moderateur\Metting;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\Moderateur\MettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class NotificationManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private MettingRepository $mettingRepository,
        private RequestStack $requestStack,
        private Security $security
    ){}

    public function init() : Notification
    {
        $notification = new Notification();
        $notification->setDateMessage(new DateTime());
        $notification->setIsRead(false);

        return $notification;
    }

    public function save(Notification $notification)
    {
		$this->em->persist($notification);
        $this->em->flush();
    }
}