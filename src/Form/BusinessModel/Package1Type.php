<?php

namespace App\Form\BusinessModel;

use App\Entity\ModerateurProfile;
use App\Entity\BusinessModel\Package;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class Package1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Nom du pack (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Nom unique du pack.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description (*)',
                'constraints' => new Sequentially([
                    new NotBlank(message:'La description est obligatoire.'),
                    new Length(
                        min: 3,
                        minMessage: 'La description est trop court',
                    ),
                ]),
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Description du pack (affichée sur la page d\'achat du pack).',
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('credit', IntegerType::class, [
                'required' => false,
                'label' => 'Crédit',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Nombre de credits offerts par le pack.',
            ])
            ->add('price', TextType::class, [
                'required' => false,
                'label' => 'Montant (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'help' => 'Montant du pack.',
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                     'Crédit' => 'CREDIT' ,
                     'Contrat' => 'CONTRAT' ,
                     'Abonnement' => 'ABONNEMENT' ,
                ],
                'required' => false,
                'label' => 'Type (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Ce champ est obligatoires.'),
                ]),
                'help' => 'Indiquez le type de pack (abonnement, crédit, contrat).'
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                     'Valide' => 'VALID' ,
                     'En attente' => 'PENDING' ,
                ],
                'required' => false,
                'label' => 'Statut (*)',
                'label_attr' => [
                    'class' => 'fw-bold fs-6' 
                ],
                'constraints' => new Sequentially([
                    new NotBlank(message:'Ce champ est obligatoires.'),
                ]),
                'help' => 'Indiquez le statut du pack (valide, en attente).'
            ])
            // ->add('moderator', EntityType::class, [
            //     'class' => ModerateurProfile::class,
            //     'choice_label' => 'id',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Package::class,
        ]);
    }
}
