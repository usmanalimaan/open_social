<?php

namespace Drupal\social_activity\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\social_group\CrossPostingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupContentOthersActivityEntityCondition' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "group_content_node_multiple_groups",
 *  label = @Translation("Node exists in multiple groups"),
 *  entities = {"group_content" = {}}
 * )
 */
class GroupContentMultipleActivityEntityCondition extends ActivityEntityConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The cross-posting service.
   *
   * @var \Drupal\social_group\CrossPostingService
   */
  protected $crossPostingService;

  /**
   * Constructs a GroupContentMultipleActivityEntityCondition object.
   *
   * @param \Drupal\social_group\CrossPostingService $cross_posting_service
   *   The group content enabler manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CrossPostingService $cross_posting_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->crossPostingService = $cross_posting_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_group.cross_posting')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    if ($entity->getEntityTypeId() === 'group_content') {
      if ($this->crossPostingService->nodeExistsInMultipleGroups($entity)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
