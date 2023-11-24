<?php

namespace App\Form\Moderateur;

use App\Entity\AffiliateTool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AffiliateToolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('lienAffiliation')
            ->add('commission')
            ->add('slug')
            ->add('type')
            ->add('image')
            ->add('customId')
            ->add('shortDescription')
            ->add('slogan')
            ->add('prix')
            // ->add('creeLe')
            // ->add('editeLe')
            ->add('status')
            ->add('featured')
            // ->add('relatedIds')
            ->add('categories')
            ->add('tags')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AffiliateTool::class,
        ]);
    }
}
