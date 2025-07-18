<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-products',
    description: 'Carga productos de ejemplo en la base de datos',
)]
class LoadProductsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $products = [
            [
                'codigo' => 'HMP-001',
                'nombre' => 'Huanarpo Macho Powder',
                'descripcion' => 'Polvo de Huanarpo Macho, suplemento natural para la energía y vitalidad.',
                'precio' => 39.25,
                'stock' => 100,
                'categoria' => 'Superfood Powders',
                'imagen' => 'huanarpo-macho.jpg',
                'estado' => 'disponible'
            ],
            [
                'codigo' => 'HGT-001',
                'nombre' => 'Hercampuri + Graviola + Tocosh Capsules',
                'descripcion' => 'Combinación de suplementos naturales para el sistema inmunológico.',
                'precio' => 37.21,
                'stock' => 75,
                'categoria' => 'Capsules',
                'imagen' => 'hercampuri-combo.jpg',
                'estado' => 'disponible'
            ],
            [
                'codigo' => 'BMP-001',
                'nombre' => 'Black Mashua Powder',
                'descripcion' => 'Polvo de Mashua Negra, rico en antioxidantes y nutrientes.',
                'precio' => 36.20,
                'stock' => 50,
                'categoria' => 'Superfood Powders',
                'imagen' => 'black-mashua.jpg',
                'estado' => 'disponible'
            ],
            [
                'codigo' => 'MCP-001',
                'nombre' => 'Maca Powder',
                'descripcion' => 'Polvo de Maca orgánica, energizante natural.',
                'precio' => 29.99,
                'stock' => 150,
                'categoria' => 'Superfood Powders',
                'imagen' => 'maca-powder.jpg',
                'estado' => 'disponible'
            ],
            [
                'codigo' => 'CMP-001',
                'nombre' => 'Camu Camu Powder',
                'descripcion' => 'Polvo de Camu Camu, alta concentración de vitamina C.',
                'precio' => 34.99,
                'stock' => 80,
                'categoria' => 'Fruit Powders',
                'imagen' => 'camu-camu.jpg',
                'estado' => 'disponible'
            ]
        ];

        foreach ($products as $productData) {
            $product = new Product();
            $product->setCodigo($productData['codigo']);
            $product->setNombre($productData['nombre']);
            $product->setDescripcion($productData['descripcion']);
            $product->setPrecio($productData['precio']);
            $product->setStock($productData['stock']);
            $product->setCategoria($productData['categoria']);
            $product->setImagen($productData['imagen']);
            $product->setEstado($productData['estado']);

            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();

        $io->success('Se han cargado ' . count($products) . ' productos en la base de datos.');

        return Command::SUCCESS;
    }
}
