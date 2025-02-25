<?php

namespace App\Form\Marketing;

use App\Entity\User;
use App\Entity\Marketing\Commission;
use Symfony\Component\Form\AbstractType;
use App\Form\Autocomplete\UserAutocompleteField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', TextType::class, [
                'required' => false,
                'label' => 'Montant',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Montant.',
            ])
            ->add('commissionPercentage', TextType::class, [
                'required' => false,
                'label' => 'Pourcentage',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Pourcentage de la commission.',
            ])
            ->add('serviceType', ChoiceType::class, [
                'choices' => Commission::getTypes(),
                'required' => false,
                'label' => 'Type',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Type de la commision.',
            ])
            ->add('saleReference', TextType::class, [
                'required' => false,
                'label' => 'Référence',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Référence de la vente.',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Commission::getStatuses(),
                'required' => false,
                'label' => 'Status',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Status de la commision.',
            ])
            ->add('user', UserAutocompleteField::class, [
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Séléctionnez un utilisateur pour commission.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Commentaire',
                'label_attr' => [
                    'class' => 'fw-bold fs-5' 
                ],
                'help' => 'Notes pour la commission.',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commission::class,
        ]);
    }
}
