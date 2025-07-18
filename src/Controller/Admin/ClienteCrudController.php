<?php

namespace App\Controller\Admin;

use App\Entity\Cliente;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class ClienteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cliente::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nombre'),
            TextField::new('apellido'),
            EmailField::new('email'),
            TextField::new('telefono'),
            TextareaField::new('direccion'),
            TextField::new('dni'),
            DateTimeField::new('fecha_registro'),
            ChoiceField::new('estado')
                ->setChoices([
                    'Activo' => 'activo',
                    'Inactivo' => 'inactivo'
                ]),
            AssociationField::new('usuario')
        ];
    }
}
