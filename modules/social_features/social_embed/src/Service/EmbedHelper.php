<?php

namespace Drupal\social_embed\Service;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

class EmbedHelper {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * Constructor for EmbedHelper.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManager $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns file path of placeholder image.
   *
   * @return string
   *   File path.
   */
  public function getEmbedPlaceholderImage(): string {
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => 'public://video_placeholder_img1.jpeg']);
    /** @var \Drupal\file\FileInterface|null $file */
    $file = reset($files) ?: NULL;
    if ($file) {
      return $file->createFileUrl();
    } else {
      $module_path = $this->moduleHandler->getModule('social_embed')->getPath();
      $file_path = $module_path . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'video_placeholder_img1.jpeg';
      $file_system = \Drupal::service('file_system');
      $uri = $file_system->copy($file_path, 'public://video_placeholder_img1.jpeg', FileSystemInterface::EXISTS_REPLACE);

      // Create a file media.
      /** @var \Drupal\file\FileInterface $file */
      $media = File::create([
        'uri' => $uri,
      ]);
      $media->setPermanent();
      $media->save();
      return $media->createFileUrl();
    }
  }
}
