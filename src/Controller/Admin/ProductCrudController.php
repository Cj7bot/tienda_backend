<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

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
            TextField::new('codigo', 'Código'),
            TextField::new('nombre', 'Nombre'),
            TextareaField::new('descripcion', 'Descripción')->hideOnIndex(),
            MoneyField::new('precio', 'Precio')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
            IntegerField::new('stock', 'Stock'),
            ChoiceField::new('categoria', 'Categoría')
                ->setChoices([
                    'Superfood Powders' => 'superfood_powders',
                    'Capsules' => 'capsules',
                    'Diabetic Control' => 'diabetic_control',
                    'Prostate Balance' => 'prostate_balance',
                    'Intestinal Wellness' => 'intestinal_wellness',
                    'Male Supplements' => 'male_supplements',
                    'Female Supplements' => 'female_supplements',
                    'Vegan Protein Powders' => 'vegan_protein_powders',
                    'Baking Flours' => 'baking_flours',
                    'Fruit Powders' => 'fruit_powders',
                    'Herbal Teas' => 'herbal_teas',
                    'Wholesale for Retailers' => 'wholesale_for_retailers',
                    'Natural Sweeteners' => 'natural_sweeteners',
                    'Herbal Powders' => 'herbal_powders'
                ])
                ->setRequired(true),
            
            // Campo de imagen para mostrar en el listado
            ImageField::new('imagen', 'Imagen')
                ->setBasePath('/uploads/products/')
                ->onlyOnIndex(),
            
            // Campo de imagen para formularios (crear/editar)
            ImageField::new('imagen', 'Imagen')
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
                ->hideOnIndex(),
                
            ChoiceField::new('estado', 'Estado')
                ->setChoices([
                    'Disponible' => 'disponible',
                    'Agotado' => 'agotado',
                    'Descontinuado' => 'descontinuado'
                ])
        ];
    }
}
