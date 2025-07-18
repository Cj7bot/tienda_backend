<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Usuario')
            ->setEntityLabelInPlural('Usuarios')
            ->setSearchFields(['email', 'nombre', 'id_usuario'])
            ->setDefaultSort(['fecha_registro' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id_usuario', 'ID')
                ->setFormTypeOption('disabled', $pageName !== Crud::PAGE_NEW),
            EmailField::new('email', 'Correo Electrónico'),
            TextField::new('password', 'Contraseña')
                ->setFormType(PasswordType::class)
                ->onlyWhenCreating(),
            TextField::new('nombre', 'Nombre'),
            ChoiceField::new('roles', 'Roles')
                ->setChoices([
                    'Usuario' => 'ROLE_USER',
                    'Administrador' => 'ROLE_ADMIN'
                ])
                ->allowMultipleChoices()
                ->renderAsBadges(),
            DateTimeField::new('fecha_registro', 'Fecha de Registro')
                ->hideOnForm(),
            ChoiceField::new('estado', 'Estado')
                ->setChoices([
                    'Activo' => 'activo',
                    'Inactivo' => 'inactivo'
                ])
                ->renderAsBadges([
                    'activo' => 'success',
                    'inactivo' => 'danger'
                ])
        ];
    }
}
