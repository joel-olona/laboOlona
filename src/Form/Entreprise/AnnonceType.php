<?php

namespace App\Form\Entreprise;

use App\Entity\Secteur;
use App\Entity\Entreprise\JobListing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class AnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, ['label' => 'Donnez un titre à votre annonce',])
            ->add('secteur', EntityType::class, [
                'label' => 'app_dashboard_company_posting_new.sector',
                'class' => Secteur::class,
                'attr' => []
            ])
            ->add('dateExpiration', DateType::class, [
                'label' => 'app_dashboard_company_posting_new.planned_date',
                'widget' => 'single_text',  
                'format' => 'yyyy-MM-dd',   
            ])
            ->add('typeContrat', ChoiceType::class, [
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Stage' => 'Stage',
                    'Alternance' => 'Alternance',
                ],
                'label' => 'app_dashboard_company_posting_new.type'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'app_dashboard_company_posting_new.desc_form',
                'required' => true,
                'attr' => [
                    'rows' => 8
                ]
            ])
            ->add('salaire', MoneyType::class, ['label' => 'app_dashboard_company_posting_new.tarif'])
            ->add('lieu', TextType::class, ['label' => 'Lieu',])
            // ->add('competences')
            // ->add('langues')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobListing::class,
        ]);
    }
}
