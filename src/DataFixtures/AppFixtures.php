<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\ModerateurProfile;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Repository\EntrepriseProfileRepository;
use DateTime;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private EntrepriseProfileRepository $entrepriseProfileRepository,
        private UserPasswordHasherInterface $encoder,
        private SluggerInterface $sluggerInterface,
    ){
    }

    public function load(ObjectManager $manager): void
    {
        // create 20 products! Bam!
        $faker = Factory::create('fr_FR');

        $job = [
            'Développeur mobile',
            'Développeur web',
            'Administrateur réseau',
            'Consultant SEO',
            'Graphiste',
            'Monteur vidéo',
            'Rédacteur web',
            'Community manager',
            'Assistant virtuel',
            'Traducteur',
            'Correcteur',
            'Développeur full stack',
        ];


        $jobdesc = [
            "Hello !

            On recherche un freelance pour automatiser la création de comptes Fruitz (application de rencontre) avec l'émulateur BlueStacks.
            
            L'objectif est d'avoir (assez rapidement) une application exécutable en local sur Windows. Nous pensons donc nécessaire de créer l'app avec plusieurs langages de codage (Python, Node, et C++)
            
            L'UI/UX devra être user-friendly, sans pour autant être jolie, nous recherchons l'efficacité avant tout. Egalement, il sera nécessaire de pouvoir faire plusieurs espaces utilisateurs, car nous avons plusieurs profils Fruitz différent à créer.
            
            Le principe est de cloner une instance BlueStacks qui servira de modèle pour les suivantes. On t'apportera plus de précision lors d'un call :)
            
            A très vite !",
            "Pour une plateforme de restitution d'un projet européen, notre association a fait le choix de l'outil YesWiki. Nous avons construit la majorité du site (arborescences, principales pages, structure des données, etc.) et cherchons désormais :
                - un prestataire (prestations 1 et 2) pour réaliser le webdesign du site (et la création d'éléments visuels de types pictogrammes),
                - un prestataire (prestations 3) pour réaliser l'intégration sous YesWiki.
                Il peut s'agir d'une équipe constituée ou de deux prestataires indépendants.",
            "- Prestation 1 : webdesign de la plateforme avec livraison des fichiers déclinés sur la base de 4 écrans types (livrables : design des interfaces en format vectoriel et ensemble des informations nécessaires pour l’intégrateur, cf. prestation 3)
            - Prestation 2 : déclinaison d'éléments d'illustration complémentaires, cohérents avec l'esthétique générale du site.
            - Prestation 3 : Intégration du design sur le gestionnaire de contenus YesWiki
            
            Dates de réalisations :
            - Date de livraison prestation 1 : 10/11/23
            - Date de livraison prestation 2 : 17/11/23
            - Date de livraison prestation 3 : 01/12/23
            
            Un cahier des charges détaillé et ses annexes est joint pour détailler nos attentes.
            
            Merci de nous transmettre vos devis détaillés (avec références, portfolio et éléments techniques) par email ou via la plateforme.",
            "Nous recherchons un freelance expert dev Wordpress/elementor/woo-commerce pour reprendre un site Wordpress (Elementor, Woo-commerce, 50 plugins, 5000 références produits, comptes clients, factures, admin fabricant différent de l'admin webmaster):",
            "Aujourd'hui, il semble périlleux de:
            - le mettre à jour (ou mettre à jour chaque plug-in)
            - Ré-upload la base de données produits avec des modifications de ces produits (en bulk) ou par exemple, ajout d'une deuxième et troisième photos pour l'ensemble des produits
            - Conserver des performances top (rapidité & stabilité) dans le temps avec ajout de plus de références et création de nombreux comptes clients
            - Mettre à jour le serveur selon une manoeuvre demandée par OVH, sans risquer de casser les plug-ins qui ne s'adapteraient pas à cete nouvelle version du serveur...",
            "Nous souhaitons donc le reprendre, sans repayer un site complet (celui ci nous a couté déjà 10k euros et il est impossible de remettre la moitié de ce budget pour la suite)...
            Cela signifie remplacer une partie des plugins par une fonctionnalité reprenant ces derniers, et ainsi assurer la maintenance possible et la rapidité et sécurité du site web dans le temps.",
            "Nous cherchons quelqu'un de disponible, qui comprendra vraiment le site, ses fonctionnalités, le projet et la sensibilité de ce site afin de tester sur un serveur bis un site copié (amélioré progressivement) puis migrer sans aucune instabilité le travail sur le serveur live afin de pouvoir améliorer ce site sans compromettre les ventes qui ont lieu chaque jour dessus, ni perdre de la data.",
            "Nous avons, à dispo une fois votre intérêt et expertise démontrés, un cahier des charges détaillé de toutes les fonctionnalités principales du site. Je serai aussi ravie de faire connaissance au tel ou en visio pour un tel projet, afin de nous assurer du fit.

            Pour info, la refonte est tout récente et le site en lui-même (design, usage, fonctionnalités) doivent rester intactes..",
        ];
        $status = ['OPEN', 'CLOSED', 'FILLED'];
        $typeContrat = [
            "CDI",
            "CDD",
            "STAGE",
            "ALTERNANCE",
        ];

        $user = new User();
        $plainPassword = '000000';
        $user->setNom('Client')
            ->setPrenom('Olona')
            ->setDateInscription(new DateTime())
            ->setType(User::ACCOUNT_MODERATEUR)
            ->setEmail('moderateur@gmail.com')
            ->setPassword($this->encoder->hashPassword($user, $plainPassword));

        $moderateur = new ModerateurProfile();
        $moderateur->setModerateur($user);
        $manager->persist($user);
        $manager->persist($moderateur);


        $entreprises = [];

        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $plainPassword = '000000';
            $user->setNom('Client')
                ->setPrenom('Olona')
                ->setDateInscription(new DateTime())
                ->setType(User::ACCOUNT_ENTREPRISE)
                ->setEmail($faker->email)
                ->setPassword($this->encoder->hashPassword($user, $plainPassword));

            $entreprise = new EntrepriseProfile();
            $entreprise->setEntreprise($user)
                ->setTaille('SM')
                ->setDescription($faker->paragraph(4))
                ->setLocalisation($faker->countryCode())
                ->setSiteWeb('http://olona-talents.com');
                $manager->persist($user);
                $manager->persist($entreprise);

            $entreprises[] = $entreprise;
        }

        for ($i = 0; $i < 20; $i++) {
            $jobListing = new JobListing();
            $jobListing->setTitre($faker->randomElement($job));
            $jobListing->setDescription($faker->randomElement($jobdesc));
            $jobListing->setDateCreation($faker->dateTime());
            $jobListing->setDateExpiration($faker->dateTime());
            $jobListing->setStatus($faker->randomElement($status));
            $jobListing->setTypeContrat($faker->randomElement($typeContrat));
            $jobListing->setSalaire(200.00);
            $jobListing->setEntreprise($faker->randomElement($entreprises));
            $manager->persist($jobListing);
        }

        $manager->flush();
    }
}
