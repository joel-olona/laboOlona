<?php

namespace App\Controller\Admin\Crud;

use App\Entity\Cron\CronLog;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CronLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CronLog::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('commandName')->setLabel('crontab'),
            DateTimeField::new('startTime')->setLabel('Début'),
            DateTimeField::new('endTime')->setLabel('Fin'),
            TextField::new('status')->setLabel('Statut'),
            IntegerField::new('emailsSent')->setLabel('Emails envoyés'),
        ];
    }
}