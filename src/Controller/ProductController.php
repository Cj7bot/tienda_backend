<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/catalog/by-category', name: 'products_by_category', methods: ['GET'])]
    public function getProductsByCategory(Request $request): JsonResponse
    {
        try {
            $category = $request->query->get('category', 'all');
            
            $repository = $this->entityManager->getRepository(Product::class);
            
            if ($category === 'all') {
                // Obtener todos los productos
                $products = $repository->findBy(['estado' => 'disponible']);
            } else {
                // Filtrar por categoría específica
                $products = $repository->findBy([
                    'categoria' => $category,
                    'estado' => 'disponible'
                ]);
            }

            $productData = [];
            foreach ($products as $product) {
                $productData[] = [
                    'id' => $product->getId(),
                    'codigo' => $product->getCodigo(),
                    'nombre' => $product->getNombre(),
                    'descripcion' => $product->getDescripcion(),
                    'precio' => $product->getPrecio(),
                    'stock' => $product->getStock(),
                    'categoria' => $product->getCategoria(),
                    'imagen' => $product->getImagen() ? '/uploads/products/' . $product->getImagen() : null,
                    'estado' => $product->getEstado()
                ];
            }

            return new JsonResponse([
                'success' => true,
                'category' => $category,
                'total' => count($productData),
                'products' => $productData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/catalog/categories-with-count', name: 'categories_with_count', methods: ['GET'])]
    public function getCategoriesWithCount(): JsonResponse
    {
        try {
            $repository = $this->entityManager->getRepository(Product::class);
            
            // Obtener todas las categorías con su conteo
            $query = $this->entityManager->createQuery(
                'SELECT p.categoria, COUNT(p.id_producto) as count 
                FROM App\Entity\Product p 
                WHERE p.estado = :estado
                GROUP BY p.categoria'
            );
            $query->setParameter('estado', 'disponible');
            $categoryStats = $query->getResult();

            // Preparar datos para el frontend
            $categories = [
                'all' => [
                    'name' => 'All',
                    'value' => 'all',
                    'count' => 0
                ]
            ];

            $totalCount = 0;
            foreach ($categoryStats as $stat) {
                $categoryKey = $stat['categoria'];
                $count = (int)$stat['count'];
                $totalCount += $count;
                
                // Mapear nombres legibles
                $categoryNames = [
                    'superfood_powders' => 'Superfood Powders',
                    'capsules' => 'Capsules',
                    'diabetic_control' => 'Diabetic Control',
                    'prostate_balance' => 'Prostate Balance',
                    'intestinal_wellness' => 'Intestinal Wellness',
                    'male_supplements' => 'Male Supplements',
                    'female_supplements' => 'Female Supplements',
                    'vegan_protein_powders' => 'Vegan Protein Powders',
                    'baking_flours' => 'Baking Flours',
                    'fruit_powders' => 'Fruit Powders',
                    'herbal_teas' => 'Herbal Teas',
                    'wholesale_for_retailers' => 'Wholesale for Retailers',
                    'natural_sweeteners' => 'Natural Sweeteners',
                    'herbal_powders' => 'Herbal Powders'
                ];

                $categories[$categoryKey] = [
                    'name' => $categoryNames[$categoryKey] ?? ucfirst(str_replace('_', ' ', $categoryKey)),
                    'value' => $categoryKey,
                    'count' => $count
                ];
            }

            // Actualizar el conteo de "All"
            $categories['all']['count'] = $totalCount;

            return new JsonResponse([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}