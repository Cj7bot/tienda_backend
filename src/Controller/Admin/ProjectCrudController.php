<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class ProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id')->hideOnForm(),
            TextField::new('name', 'Nombre'),
            TextareaField::new('description', 'Descripción'),
            DateTimeField::new('startDate', 'Fecha de inicio'),
            DateTimeField::new('endDate', 'Fecha de fin'),
            ChoiceField::new('status', 'Estado')
                ->setChoices([
                    'Pendiente' => 'pending',
                    'En progreso' => 'in_progress',
                    'Completado' => 'completed',
                    'Cancelado' => 'cancelled'
                ]),
            ChoiceField::new('priority', 'Prioridad')
                ->setChoices([
                    'Alta' => 'high',
                    'Media' => 'medium',
                    'Baja' => 'low'
                ])
        ];
    }
}
