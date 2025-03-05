<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Marketing\Commission;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CommissionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commission::class;
    }
    
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setDefaultSort(['id' => 'DESC'])
            ->setEntityLabelInSingular('Commission')
            ->setEntityLabelInPlural('Commissions')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(8),
            IdField::new('id')->hideOnForm(),
            NumberField::new('amount', 'Montant'),
            TextField::new('saleReference', 'Référence achat')->hideOnIndex(),
            TextEditorField::new('description', 'Description')->hideOnIndex(),
            DateField::new('createdAt', 'Crée le')->hideOnForm(),
            ChoiceField::new('status', 'Statut')->setChoices(Commission::getStatuses())->hideOnIndex(),
            TextField::new('serviceType', 'Type'),
            NumberField::new('fixedAmount', 'Montant Final')->hideOnIndex(),
            NumberField::new('commissionPercentage', 'Pourcentage'),
            FormField::addColumn(4),
            TextField::new('user', 'Utilisateur'),
            TextField::new('user.email', 'Email')->hideOnIndex(),
            TextField::new('user.telephone', 'Téléphone')->hideOnIndex(),
            TextField::new('user.affiliateCode', 'Code affiliation')->hideOnIndex(),
        ];
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
