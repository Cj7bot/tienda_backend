<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id', 'ID')->hideOnForm(),
            AssociationField::new('client', 'Cliente'),
            DateTimeField::new('orderDate', 'Fecha de Pedido'),
            MoneyField::new('total', 'Total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            ChoiceField::new('status', 'Estado')
                ->setChoices([
                    'Pendiente' => 'pending',
                    'En Proceso' => 'processing',
                    'Completado' => 'completed',
                    'Cancelado' => 'cancelled'
                ]),
            TextareaField::new('notes', 'Notas')
        ];
    }
}
