<?php

namespace App\EventListener;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductImageListener implements EventSubscriberInterface
{
    public function __construct(
        private string $uploadDir = 'public/uploads/products/'
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'handleImageUpload',
            BeforeEntityUpdatedEvent::class => 'handleImageUpload',
        ];
    }

    public function handleImageUpload($event): void
    {
        $entity = $event->getEntityInstance();
        
        if (!$entity instanceof Product) {
            return;
        }

        $imagen = $entity->getImagen();
        
        if ($imagen instanceof UploadedFile) {
            // Generar nombre único para la imagen
            $originalFilename = pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                $originalFilename
            );
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imagen->guessExtension();

            try {
                // Mover el archivo al directorio de uploads
                $imagen->move($this->uploadDir, $newFilename);
                
                // Actualizar la entidad con el nombre del archivo
                $entity->setImagen($newFilename);
                
            } catch (\Exception $e) {
                // En caso de error, mantener el valor anterior o null
                $entity->setImagen(null);
            }
        }
    }
}