<?php

namespace App\Form\Profile\Candidat;

use App\Entity\Candidate\Competences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CompetencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'app_identity_expert_step_two.skill.name',
            ])
            ->add('note', ChoiceType::class, [
                'label' => 'app_identity_expert_step_two.skill.level',
                'choices'  => [
                    'app_identity_expert_step_two.skill.one' => 1,
                    'app_identity_expert_step_two.skill.two' => 2,
                    'app_identity_expert_step_two.skill.three' => 3,
                    'app_identity_expert_step_two.skill.four' => 4,
                    'app_identity_expert_step_two.skill.five' => 5,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'app_identity_expert_step_two.experience.description',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'ckeditor-textarea'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'app_identity_expert_step_two.skill.submit',
                'attr' => [
                    'class' => 'btn btn-dark rounded-pill'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competences::class,
        ]);
    }
}
