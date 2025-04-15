<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class DatabaseActivitySubscriber
{
    private $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->logActivity('remove', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        // Pour le moment, tu peux laisser vide ou ajouter du code plus tard
        // Exemple de log :
        // $entity = $args->getObject();
        // $this->logger->info('Updated entity: ' . get_class($entity));
    }

    public function logActivity(string $action, mixed $entity): void
    {
        if ($entity instanceof Product && $action === "remove") {
            $imageUrls = $entity->getImageUrls();
            if (is_array($imageUrls) && count($imageUrls) > 0) {
                foreach ($imageUrls as $filename) {
                    if ($filename) {
                        $filePath = $this->projectDir . '/public/assets/images/products/' . $filename;
                        if (file_exists($filePath) && is_file($filePath)) {
                            @unlink($filePath); // suppression silencieuse
                        }
                    }
                }
            }
        }

        if ($entity instanceof Category && $action === "remove") {
            $filename = $entity->getImageUrl();
            if ($filename) {
                $filePath = $this->projectDir . '/public/assets/images/categories/' . $filename;
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
        }
    }
}
