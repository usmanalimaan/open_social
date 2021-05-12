<?php

namespace Drupal\social_activity\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
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
class GroupContentMultipleActivityEntityCondition extends GroupContentSingleActivityEntityCondition {

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    if ($entity->getEntityTypeId() === 'group_content') {
      // We apply the condition only for node group content.
      if (!in_array($entity->getGroupContentType()->id(), $this->getValidGroupContentPluginIds(), TRUE)) {
        return TRUE;
      }

      if ($this->nodeExistsInMultipleGroups($entity)) {
        return TRUE;
      }
    }

    return FALSE;
  }
}
