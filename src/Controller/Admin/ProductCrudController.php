<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IntegerField::new('id', 'ID')->hideOnForm(),
            TextField::new('nombre', 'Nombre'),
            TextareaField::new('descripcion', 'Descripción')->hideOnIndex(),
            MoneyField::new('precio', 'Precio')
                ->setCurrency('USD')
                ->setStoredAsCents(false),
            IntegerField::new('stock', 'Stock'),
            AssociationField::new('categoria', 'Categoría'),

            // Campo de imagen para mostrar en el listado
            ImageField::new('imagenProducto', 'Imagen')
                ->setBasePath('/uploads/products/')
                ->onlyOnIndex(),

            // Campo de imagen para formularios (crear/editar)
            ImageField::new('imagenProducto', 'Imagen')
                ->setBasePath('/uploads/products/')
                ->setUploadDir('public/uploads/products/')
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false)
                ->setFormTypeOptions([
                    'upload_filename' => function($file) {
                        return sprintf('product_%s_%s.%s',
                            uniqid(),
                            date('Y_m_d_H_i_s'),
                            $file->guessExtension()
                        );
                    }
                ])
                ->hideOnIndex()
        ];
    }
}
