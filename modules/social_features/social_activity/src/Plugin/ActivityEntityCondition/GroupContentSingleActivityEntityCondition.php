<?php

namespace Drupal\social_activity\Plugin\ActivityEntityCondition;

use Drupal\activity_creator\Plugin\ActivityEntityConditionBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function MongoDB\Driver\Monitoring\removeSubscriber;

/**
 * Provides a 'GroupContentFirstActivityEntityCondition' activity condition.
 *
 * @ActivityEntityCondition(
 *  id = "group_content_node_single_group",
 *  label = @Translation("Node exists in single group"),
 *  entities = {"group_content" = {}}
 * )
 */
class GroupContentSingleActivityEntityCondition extends ActivityEntityConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentEnabler;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a GroupContentMultipleActivityEntityCondition object.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $group_content_manager
   *   The group content enabler manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupContentEnablerManagerInterface $group_content_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupContentEnabler = $group_content_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isValidEntityCondition($entity) {
    if ($entity->getEntityTypeId() === 'group_content') {
      // We apply the condition only for node group content.
      if (!in_array($entity->getGroupContentType()->id(), $this->getValidGroupContentPluginIds(), TRUE)) {
        return TRUE;
      }

      // If node is added only to one group then condition is valid.
      if (!$this->nodeExistsInMultipleGroups($entity)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns number of groups node is added as a content.
   *
   * @param \Drupal\group\Entity\GroupContentInterface $group_content
   *   Group content entity.
   *
   * @return boolean
   *   Returns flag if node exists in multiple groups.
   */
  protected function nodeExistsInMultipleGroups(GroupContentInterface $group_content): bool {
    $valid_plugins = $this->getValidGroupContentPluginIds();

    // Get node id.
    $sub_query = $this->database->select('group_content_field_data', 'gc');
    $sub_query->addField('gc', 'entity_id');
    $sub_query->condition('gc.id', $group_content->id());

    // Get count of group content with the current node.
    $query = $this->database->select('group_content_field_data', 'gc');
    $query->addField('gc', 'id');
    $query->condition('gc.entity_id', $sub_query);
    $query->condition('gc.type', $valid_plugins, 'IN');

    // If for the current node we have some actions in other groups
    // do not send duplicate emails.
    return ((int) $query->countQuery()->execute()->fetchField()) > 1;
  }

  /**
   * Returns existed group content plugins applicable to nodes.
   *
   * @return array
   *   An array with plugin ids.
   */
  protected function getValidGroupContentPluginIds(): array {
    $group_content_plugin_ids = array_filter($this->groupContentEnabler->getInstalledIds(), function ($string) {
      return strpos($string, 'group_node:') === 0;
    });

    $plugins = [];
    foreach ($group_content_plugin_ids as $plugin_id) {
      $plugins = array_merge($plugins, $this->groupContentEnabler->getGroupContentTypeIds($plugin_id));
    }

    return $plugins;
  }
}
