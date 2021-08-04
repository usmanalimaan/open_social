<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\Php;
use Drupal\Component\Uuid\Uuid;
use Drupal\filter\FilterProcessResult;
use Drupal\url_embed\Plugin\Filter\UrlEmbedFilter;
use Drupal\url_embed\UrlEmbedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to display embedded URLs based on data attributes.
 *
 * @Filter(
 *   id = "social_embed_url_embed",
 *   title = @Translation("Display embedded URLs with consent"),
 *   description = @Translation("Embeds URLs using data attribute: data-embed-url."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class SocialEmbedUrlEmbedFilter extends UrlEmbedFilter {

  /**
   * Uuid services.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected Php $uuid;

  /**
   * Constructs a UrlEmbedFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\url_embed\UrlEmbedInterface $url_embed
   *   The URL embed service.
   * @param \Drupal\Component\Uuid\Php $uuid
   *   The uuid services.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UrlEmbedInterface $url_embed, Php $uuid) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $url_embed);
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('url_embed'),
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (strpos($text, 'data-embed-url') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      foreach ($xpath->query('//drupal-url[@data-embed-url]') as $node) {
        /** @var \DOMElement $node */
        $url = $node->getAttribute('data-embed-url');
        $url_output = '';
        try {
          // Replace URL with consent button.
          $uuid = $this->uuid->generate();
          $url_output = "<div class='social-embed-container' id='social-embed-placeholder'><div id='social-embed-iframe-$uuid'><a class='use-ajax btn btn-flat waves-effect waves-btn' href='/api/opensocial/social-embed/generate?url=$url&uuid=$uuid'>Show content</a></div></div>";
        }
        catch (\Exception $e) {
          watchdog_exception('url_embed', $e);
        } finally {
          // If the $url_output is empty, that means URL is non-embeddable.
          // So, we return the original url instead of blank output.
          if ($url_output === NULL || $url_output === '') {
            // The reason of using _filter_url() function here is to make
            // sure that the maximum URL cases e.g., emails are covered.
            $url_output = UrlHelper::isValid($url) ? _filter_url($url, $this) : $url;
          }
        }

        $this->replaceNodeContent($node, $url_output);
      }

      $result->setProcessedText(Html::serialize($dom));
    }
    return $result;
  }

}
