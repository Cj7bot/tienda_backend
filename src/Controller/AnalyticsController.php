<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\EventListener\AnalyticsEventListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AnalyticsController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/api/analytics/realtime-data', name: 'analytics_realtime_data', methods: ['GET'])]
    public function getRealtimeData(): JsonResponse
    {
        try {
            // Datos por hora de las últimas 24 horas
            $now = new \DateTime();
            $realtimeData = [
                'labels' => [],
                'compras' => [],
                'productos_creados' => []
            ];

            // Crear labels para las últimas 24 horas
            for ($i = 23; $i >= 0; $i--) {
                $hour = (clone $now)->modify("-{$i} hours");
                $realtimeData['labels'][] = $hour->format('H:i');
            }

            // Inicializar arrays
            $comprasHourly = array_fill(0, 24, 0);
            $productosHourly = array_fill(0, 24, 0);

            // Obtener eventos recientes del listener
            $logFile = sys_get_temp_dir() . '/analytics_events.json';
            
            if (file_exists($logFile)) {
                $events = json_decode(file_get_contents($logFile), true) ?? [];
                
                foreach ($events as $event) {
                    try {
                        $eventTime = new \DateTime($event['timestamp']);
                        $hoursDiff = $now->diff($eventTime)->h;
                        
                        if ($hoursDiff < 24) {
                            $index = 23 - $hoursDiff;
                            if ($index >= 0 && $index < 24) {
                                if ($event['type'] === 'compra') {
                                    $comprasHourly[$index]++;
                                } elseif ($event['type'] === 'producto_creado') {
                                    $productosHourly[$index]++;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignorar eventos con formato incorrecto
                        continue;
                    }
                }
            }
            
            // Agregar algunos datos simulados para demostración
            for ($i = 0; $i < 24; $i++) {
                $comprasHourly[$i] += rand(0, 3);
                $productosHourly[$i] += rand(0, 2);
            }

            $realtimeData['compras'] = $comprasHourly;
            $realtimeData['productos_creados'] = $productosHourly;

            return new JsonResponse([
                'success' => true,
                'data' => $realtimeData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/analytics/add-event', name: 'analytics_add_event', methods: ['POST'])]
    public function addEvent(): JsonResponse
    {
        try {
            // Simular evento añadido (en una implementación real, esto vendría de los datos del request)
            $eventData = [
                'timestamp' => (new \DateTime())->format('c'),
                'type' => rand(0, 1) ? 'compra' : 'producto_creado',
                'value' => rand(1, 100)
            ];

            return new JsonResponse([
                'success' => true,
                'event' => $eventData
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/categories', name: 'get_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        $categories = [
            'all' => 'All',
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

        return new JsonResponse([
            'success' => true,
            'categories' => $categories
        ]);
    }
}