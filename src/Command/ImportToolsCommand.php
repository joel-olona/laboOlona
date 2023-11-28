<?php 

namespace App\Command;

use DateTime;
use App\Data\ImportData;
use App\Twig\AppExtension;
use App\Service\WooCommerce;
use App\Entity\AffiliateTool;
use App\Entity\AffiliateTool\Tag;
use App\Service\OpenAITranslator;
use App\Entity\AffiliateTool\Category;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AffiliateToolRepository;
use Symfony\Component\Console\Command\Command;
use App\Repository\AffiliateTool\TagRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use App\Repository\AffiliateTool\CategoryRepository;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:import-tools',
    description: 'Import AffiliateTools from postin.store',
    hidden: false,
    aliases: ['app:import-tools']
)]
class ImportToolsCommand extends Command
{
    public function __construct(
        private WooCommerce $wooCommerce,
        private AffiliateToolRepository $affiliateToolRepository,
        private SluggerInterface $sluggerInterface,
        private EntityManagerInterface $em,
        private AppExtension $appExtension,
        private OpenAITranslator $openAITranslator,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ){
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            // configure an argument
            ->addArgument('per_page', InputArgument::REQUIRED, 'Number of AffiliateTools per page.')
            ->addArgument('page', InputArgument::REQUIRED, 'Page.')
            // ...
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // outputs multiple lines to the console (adding "\n" at the end of each line)
        $output->writeln([
            'Import Affiliate Tool from postin.store',
            '=======================================',
            '',
        ]);

        // the value returned by someMethod() can be an iterator (https://php.net/iterator)
        // that generates and returns the messages with the 'yield' PHP keyword
        $importData = new ImportData();
        $importData->page = $input->getArgument('page');
        $importData->per_page = $input->getArgument('per_page');
        $products = $this->wooCommerce->importProduct($importData);
        
        foreach ($products as $product) {

            $affiliateTool = $this->affiliateToolRepository->findOneBy(['slug' => $this->sluggerInterface->slug(strtolower($product['name']))]);

            if(!$affiliateTool instanceof AffiliateTool){
                $output->writeln('Création de l\'outil '. $product['name']);
                $affiliateTool = new AffiliateTool();
                $affiliateTool->setSlug($this->sluggerInterface->slug(strtolower($product['name'])));
                $output->writeln(' -> Création terminée ');
            }

            $output->writeln('Mise à jour de '. $product['name']);
            $affiliateTool->setNom($product['name']);
            $affiliateTool->setDescription($product['description']);
            $affiliateTool->setDescriptionEn($this->appExtension->filterContent($this->appExtension->doShortcode($product['description'])));
            $output->writeln('Traduction de la description de '. $product['name']);
            $affiliateTool->setDescriptionFr($this->openAITranslator->translate(
                $this->appExtension->filterContent($this->appExtension->doShortcode($product['description'])) ,
                    'en',
                    'fr'
            ));
            $output->writeln(' -> Traduction terminée ');
            $affiliateTool->setLienAffiliation($product['external_url']);
            $affiliateTool->setCommission(0.90);
            $affiliateTool->setType($product['status']);
            $affiliateTool->setImage($product['images'][0]->src);
            $affiliateTool->setCustomId($product['id']);
            $output->writeln('Traduction de la description courte de '. $product['name']);
            $affiliateTool->setShortDescription($product['short_description']);
            $affiliateTool->setShortDescriptionFr($this->openAITranslator->translate(
                $this->appExtension->filterContent($this->appExtension->doShortcode($product['short_description'])) ,
                    'en',
                    'fr'
            ));
            $affiliateTool->setSlogan($product['slogan']);
            $output->writeln('Traduction du slogan '. $product['name']);
            $affiliateTool->setSloganFr($this->openAITranslator->translateCategory(
                $this->appExtension->filterContent($this->appExtension->doShortcode($product['slogan'])) ,
                    'en',
                    'fr'
            ));
            $output->writeln(' -> Traductions terminées ');
            $affiliateTool->setPrix(number_format(floatval($product['price']), 2, '.', ''));
            $affiliateTool->setCreeLe(new DateTime($product['date_created']));
            $affiliateTool->setEditeLe(new DateTime());
            $affiliateTool->setRelatedIds($product['related_ids']);

            foreach ($product['categories'] as $category) {

                $aIcategory = $this->categoryRepository->findOneBy(['slug' => $this->sluggerInterface->slug(strtolower(html_entity_decode($category->name)))]);

                if(!$aIcategory instanceof Category){
                    $output->writeln('Catégorie '. html_entity_decode($category->name));
                    $aIcategory = new Category();
                    $aIcategory->setSlug($this->sluggerInterface->slug(strtolower(html_entity_decode($category->name))));
                }else{
                    $output->writeln('Catégorie '. html_entity_decode($category->name));
                }

                $aIcategory->setnom(html_entity_decode($category->name));
                $aIcategory->setNomFr($this->openAITranslator->translateCategory(
                    html_entity_decode($category->name) ,
                    'en',
                    'fr'
                ));
                $aIcategory->setDescription($this->openAITranslator->generateDescription(
                    html_entity_decode($category->name) ,
                ));

                $this->em->persist($aIcategory);
                $affiliateTool->addCategory($aIcategory);
                $output->writeln(' -> Ajoutée à '. $product['name']);
            }

            foreach ($product['tags'] as $tag) {

                $aItag = $this->tagRepository->findOneBy(['slug' => $this->sluggerInterface->slug(strtolower(html_entity_decode($tag->name)))]);

                if(!$aItag instanceof Tag){
                    $output->writeln('Etiquette '. html_entity_decode($tag->name));
                    $aItag = new Tag();
                    $aItag->setSlug($this->sluggerInterface->slug(strtolower(html_entity_decode($tag->name))));
                }else{
                    $output->writeln('Etiquette '. html_entity_decode($tag->name));
                }

                $aItag->setnom(html_entity_decode($tag->name));
                $aItag->setNomFr($this->openAITranslator->translateCategory(
                    html_entity_decode($tag->name) ,
                    'en',
                    'fr'
                ));
                $aItag->setDescription($this->openAITranslator->generateDescription(
                    html_entity_decode($tag->name) ,
                ));

                $this->em->persist($aItag);
                $affiliateTool->addTag($aItag);
                $output->writeln(' -> Ajoutée à '. $product['name']);
            }

            $this->em->persist($affiliateTool);
            $output->writeln(' ---> Modification terminée pour '. $product['name']);
        }

        $this->em->flush();


        // outputs a message followed by a "\n"
        $output->writeln('Whoa!');

        // outputs a message without adding a "\n" at the end of the line
        $output->writeln('You are about to ');
        $output->writeln('importing AffiliateTools on Olona Talents.');

        return Command::SUCCESS;
    }
}