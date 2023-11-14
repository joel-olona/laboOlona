<?php

namespace App\Form\Search\Candidat;

use App\Manager\ModerateurManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ModerateurCandidatSearchType extends AbstractType
{

    public function __construct(
        private ModerateurManager $moderateurManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom ou prénoms ...',
                ]
            ])
            ->add('titre', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom de poste ...',
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Valide' => 'VALID',
                    'En attente' => 'PENDING',
                    'Banni' => 'BANNISHED',
                    'Mis en avant' => 'FEATURED',
                    'Réservé' => 'RESERVED',
                ],
                'required' => false,
                'label' => false,
                'placeholder' => 'Statut ...',
            ])
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