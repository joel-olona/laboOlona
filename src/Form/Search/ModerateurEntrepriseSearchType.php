<?php

namespace App\Form\Search;

use App\Entity\Entreprise\JobListing;
use App\Manager\ModerateurManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ModerateurEntrepriseSearchType extends AbstractType
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
                    'placeholder' => 'Nom de l\'entreprise ...',
                ]
            ])
            ->add('secteur', ChoiceType::class, [
                'choices' => $this->moderateurManager->getSecteurChoice(),
                'required' => false,
                'label' => false,
                'placeholder' => 'Secteur d\'activité ...',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Valide' => 'VALID',
                    'En attente' => 'PENDING',
                    'Premium' => 'PREMIUM',
                ],
                'required' => false,
                'label' => false,
                'placeholder' => 'Status ...',
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