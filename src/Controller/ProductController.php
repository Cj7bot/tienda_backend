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
                $products = $repository->findAll();
            } else {
                // Filtrar por categoría específica usando el ID de categoría
                $categoriaRepository = $this->entityManager->getRepository(\App\Entity\Categoria::class);
                $categoriaEntity = $categoriaRepository->findOneBy(['nombre' => $category]);

                if ($categoriaEntity) {
                    $products = $repository->findBy(['categoria' => $categoriaEntity]);
                } else {
                    $products = [];
                }
            }

            $productData = [];
            foreach ($products as $product) {
                $productData[] = [
                    'id' => $product->getId(),
                    'nombre' => $product->getNombre(),
                    'descripcion' => $product->getDescripcion(),
                    'precio' => $product->getPrecio(),
                    'stock' => $product->getStock(),
                    'categoria' => $product->getCategoria()->getNombre(),
                    'imagen' => $product->getImagenProducto() ? '/uploads/products/' . $product->getImagenProducto() : null
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
            // Obtener todas las categorías con su conteo
            $query = $this->entityManager->createQuery(
                'SELECT c.nombre, COUNT(p.id_producto) as count
                FROM App\Entity\Categoria c
                LEFT JOIN App\Entity\Product p WITH p.categoria = c
                GROUP BY c.id_categoria, c.nombre'
            );
            $categoryStats = $query->getResult();

            // Preparar datos para el frontend
            $categories = [];
            $totalCount = 0;

            foreach ($categoryStats as $stat) {
                $categoryName = $stat['nombre'];
                $count = (int)$stat['count'];

                if ($categoryName !== 'All') {
                    $totalCount += $count;
                }

                $categories[] = [
                    'name' => $categoryName,
                    'value' => strtolower(str_replace(' ', '_', $categoryName)),
                    'count' => $count
                ];
            }

            // Agregar "All" al principio con el total
            array_unshift($categories, [
                'name' => 'All',
                'value' => 'all',
                'count' => $totalCount
            ]);

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