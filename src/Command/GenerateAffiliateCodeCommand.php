<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Service\User\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-affiliate-code',
    description: 'Add affiliate code to users',
    hidden: false,
    aliases: ['app:generate-affiliate-code']
)]
class GenerateAffiliateCodeCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserService $userService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Génération des liens affiliation pour les utilisateurs.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findLimitedWithNullAffiliateCode(500);
        foreach ($users as $key => $user) {
            if($user->getAffiliateCode() === null){
                $user->setAffiliateCode($this->userService->generateAffiliateCode($user));
                $this->entityManager->persist($user);
            }
            $io->writeln('Génération des affiliate code pour '. $user->getId());
        }

        $this->entityManager->flush();

        $io->success('Les liens affiliation ont été ajoutés avec succès.');

        return Command::SUCCESS;
    }
}