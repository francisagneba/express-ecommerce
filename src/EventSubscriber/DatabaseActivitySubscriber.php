<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DatabaseActivitySubscriber implements EventSubscriberInterface
{
    private $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir'); // Get the project directory
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove,
        ];
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->logActivity('remove', $args->getObject());
    }

    public function logActivity(string $action, mixed $entity): void
    {
        // Handle Product removal (if needed in the future)
        if ($entity instanceof Product && $action === "remove") {
            $imageUrls = $entity->getImageUrls(); // Assuming this returns an array of URLs
            if (is_array($imageUrls) && count($imageUrls) > 0) {
                foreach ($imageUrls as $filename) {
                    if ($filename) {
                        // Construct the full path to the image file
                        $filePath = $this->projectDir . '/public/assets/images/products/' . $filename;

                        // Check if the file exists before attempting to delete it
                        if (file_exists($filePath) && is_file($filePath)) {
                            // Attempt to remove the file
                            if (!unlink($filePath)) {
                                // Optional: Log or handle the case where the file could not be deleted
                                // For example: throw new \Exception("File could not be deleted: " . $filePath);
                            }
                        } else {
                            // Optional: Handle the case where the file does not exist
                            // For example, log that the file wasn't found
                            // $this->logger->warning("File not found: " . $filePath);
                        }
                    }
                }
            }
        }

        // Handle Category removal
        if ($entity instanceof Category && $action === "remove") {
            $filename = $entity->getImageUrl(); // Assuming this returns a single file name
            if ($filename) {
                // Construct the full path to the image file
                $filePath = $this->projectDir . '/public/assets/images/categories/' . $filename;

                // Check if the file exists before attempting to delete it
                if (file_exists($filePath) && is_file($filePath)) {
                    // Attempt to remove the file
                    if (!unlink($filePath)) {
                        // Optional: Log or handle the case where the file could not be deleted
                        // For example: throw new \Exception("File could not be deleted: " . $filePath);
                    }
                } else {
                    // Optional: Handle the case where the file does not exist
                    // For example, log that the file wasn't found
                    // $this->logger->warning("File not found: " . $filePath);
                }
            }
        }
    }
}