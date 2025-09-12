<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, method: 'onProductCreated', entity: Product::class)]
#[AsEntityListener(event: Events::postPersist, method: 'onOrderCreated', entity: Order::class)]
class AnalyticsEventListener
{
    private array $realtimeEvents = [];

    public function onProductCreated(Product $product, LifecycleEventArgs $event): void
    {
        $this->addRealtimeEvent([
            'type' => 'producto_creado',
            'timestamp' => new \DateTime(),
            'entity_id' => $product->getId(),
            'data' => [
                'nombre' => $product->getNombre(),
                'precio' => $product->getPrecio()
            ]
        ]);
    }

    public function onOrderCreated(Order $order, LifecycleEventArgs $event): void
    {
        $this->addRealtimeEvent([
            'type' => 'compra',
            'timestamp' => new \DateTime(),
            'entity_id' => $order->getId(),
            'data' => [
                'total' => $order->getTotal(),
                'status' => $order->getStatus()
            ]
        ]);
    }

    private function addRealtimeEvent(array $eventData): void
    {
        // En una implementación real, esto podría:
        // 1. Guardar en Redis para acceso rápido
        // 2. Enviar via WebSocket a clientes conectados
        // 3. Guardar en una tabla de eventos para análisis
        
        $this->realtimeEvents[] = $eventData;
        
        // Para demo, almacenar en archivo temporal
        $logFile = sys_get_temp_dir() . '/analytics_events.json';
        
        $existingEvents = [];
        if (file_exists($logFile)) {
            $existingEvents = json_decode(file_get_contents($logFile), true) ?? [];
        }
        
        $existingEvents[] = $eventData;
        
        // Mantener solo los últimos 100 eventos
        if (count($existingEvents) > 100) {
            $existingEvents = array_slice($existingEvents, -100);
        }
        
        file_put_contents($logFile, json_encode($existingEvents, JSON_PRETTY_PRINT));
    }

    public function getRecentEvents(int $limit = 50): array
    {
        $logFile = sys_get_temp_dir() . '/analytics_events.json';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $events = json_decode(file_get_contents($logFile), true) ?? [];
        
        return array_slice($events, -$limit);
    }
}