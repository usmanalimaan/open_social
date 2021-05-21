<?php

namespace Drupal\social_activity\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Filter duplicate notifications created on group content insert.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("activity_aggregate_gc")
 */
class AggregateNotificationsGroupContent extends FilterPluginBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The group content enabler manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $groupContentEnablerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, ConfigFactoryInterface $config_factory, GroupContentEnablerManagerInterface $group_content_enabler_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->groupContentEnablerManager = $group_content_enabler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('plugin.manager.group_content_enabler')
    );
  }

  /**
   * Not exposable.
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Filters out activity items by the taxonomy tags.
   */
  public function query() {
    // We want to apply this filter if cross-posting is enabled.
    if (!$this->configFactory->get('social_group.settings')->get('cross_posting.status')) {
      return;
    }

    $account = $this->view->getUser();

    $group_content_plugin_ids = array_filter($this->groupContentEnablerManager->getInstalledIds(), function ($string) {
      return strpos($string, 'group_node:') === 0;
    });

    $valid_plugins = [];
    foreach ($group_content_plugin_ids as $plugin_id) {
      $valid_plugins = array_merge($valid_plugins, $this->groupContentEnablerManager->getGroupContentTypeIds($plugin_id));
    }

    // Get activities with group content.
    $query = $this->database->select('activity__field_activity_recipient_user', 'afar');
    $query->addField('node', 'nid');
    $query->addField('afar', 'entity_id');

    $query->condition('afar.field_activity_recipient_user_target_id', $account->id());
    $query->condition('afae.field_activity_entity_target_type', 'group_content');
    $query->condition('gc.type', $valid_plugins, 'IN');

    $query->leftJoin(
      'activity__field_activity_entity',
      'afae',
      'afae.entity_id = afar.entity_id'
    );

    $query->leftJoin(
      'group_content_field_data',
      'gc',
      'afae.field_activity_entity_target_id = gc.id'
    );

    $query->innerJoin(
      'node',
      'node',
      'node.nid = gc.entity_id'
    );

    $result = $query->execute()->fetchAll(\PDO::FETCH_GROUP);

    $aids = [];
    foreach ($result as $items) {
      foreach ($items as $index => $item) {
        // We skip the first item as is should be shown.
        if ($index == 0) {
          continue;
        }
        $aids[] = $item->entity_id;
      }
    }

    $this->query->addWhere(0, 'id', $aids ?: [0], 'NOT IN');
  }

}
