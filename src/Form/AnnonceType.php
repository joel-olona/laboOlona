<?php

namespace App\Form;

use App\Entity\Entreprise\JobListing;
use App\Form\Moderateur\SecteurType;
use App\Repository\SecteurRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AnnonceType extends AbstractType
{
    private $secteurRepository;

    public function __construct(SecteurRepository $secteurRepository)
    {
        $this->secteurRepository = $secteurRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [])
            ->add('description', TextareaType::class, [])
            ->add('dateExpiration', DateType::class, [])
            ->add('salaire', MoneyType::class, [])
            ->add('secteur', EntityType::class, [
                'class' => SecteurType::class,
                'choice_label' => 'nom',
                'label' => 'app_identity_company.sector_multiple',
                'autocomplete' => true,
                'multiple' => true,
                'expanded' => true,
                'choices' => $this->secteurRepository->findAll(),
            ])
            ->add('type_contrat', ChoiceType::class, [
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Stage' => 'Stage',
                    'Alternance' => 'Alternance',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobListing::class,
            'secteur_repository' => null,
        ]);

        $resolver->setRequired('secteur_repository');
    }
}