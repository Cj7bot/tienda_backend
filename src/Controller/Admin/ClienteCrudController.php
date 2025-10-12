<?php

namespace App\Controller\Admin;

use App\Entity\Cliente;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ClienteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cliente::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Cliente')
            ->setEntityLabelInPlural('Clientes')
            ->setSearchFields(['email', 'nombre', 'apellido', 'dni'])
            ->setDefaultSort(['fecha_registro' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nombre', 'Nombre'),
            TextField::new('apellido', 'Apellido'),
            EmailField::new('email', 'Correo Electrónico'),
            TextField::new('password', 'Contraseña')
                ->setFormType(PasswordType::class)
                ->onlyWhenCreating(),
            TextField::new('telefono', 'Teléfono'),
            TextareaField::new('direccion', 'Dirección'),
            TextField::new('dni', 'DNI'),
            BooleanField::new('estado', 'Activo'),
            ChoiceField::new('roles', 'Roles')
                ->setChoices([
                    'Usuario' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN'
                ])
                ->allowMultipleChoices()
                ->renderAsBadges(),
            DateTimeField::new('fecha_registro', 'Fecha de Registro')
                ->hideOnForm()
        ];
    }
}
