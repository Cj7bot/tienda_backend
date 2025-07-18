<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('id')->hideOnForm(),
            TextField::new('name', 'Nombre'),
            TextareaField::new('description', 'Descripción'),
            MoneyField::new('price', 'Precio')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            IntegerField::new('stock', 'Stock'),
            TextField::new('category', 'Categoría'),
            ChoiceField::new('status', 'Estado')
                ->setChoices([
                    'Activo' => 'active',
                    'Inactivo' => 'inactive',
                    'Agotado' => 'out_of_stock'
                ])
        ];
    }
}
