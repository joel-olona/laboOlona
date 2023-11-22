<?php

namespace App\Form\Search\Secteur;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ToolSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => 'Rechercher',
                'attr' => [
                    'placeholder' => 'Entrez votre recherche...',
                ]
            ])
            // ->add('type', TextType::class, [
            //     'required' => false,
            //     'label' => 'Rechercher',
            //     'attr' => [
            //         'placeholder' => 'Entrez votre recherche...',
            //     ]
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}