<?php

namespace Drupal\social_embed\Service;

use Drupal\filter\FilterProcessResult;

/**
 * Service class for Social Embed.
 */
class SocialEmbedHelper {

  /**
   * Adds given cache tag and drupal ajax library.
   *
   * @param \Drupal\filter\FilterProcessResult $result
   *   FilterProcessResult object on which changes need to happen.
   * @param string $tag
   *   Tag to add.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The object itself.
   *
   * @see \Drupal\social_embed\Plugin\Filter\SocialEmbedConvertUrlToEmbedFilter
   * @see \Drupal\social_embed\Plugin\Filter\SocialEmbedUrlEmbedFilter
   */
  public function addDependencies(FilterProcessResult $result, string $tag): FilterProcessResult {
    // Add our custom tag so that we invalidate them when site manager
    // changes consent settings.
    // @see EmbedConsentForm
    $result->addCacheTags([$tag]);
    // We need this library to be attached as we are using 'use-ajax'
    // class in the show consent button markup.
    $result->addAttachments([
      'library' => [
        'core/drupal.ajax',
        'social_embed/consent-placeholder',
      ],
    ]);

    return $result;
  }

  /**
   * Checks if item is on the whitelist.
   *
   * @param string $text
   *   The item to check for.
   *
   * @return bool
   *   Return if the item is on the whitelist or not.
   */
  public function whiteList($text) {
    // Fetch allowed patterns.
    $patterns = $this->getPatterns();

    // Check if the URL provided is from a whitelisted site.
    foreach ($patterns as $pattern) {
      // Testing pattern.
      $testing_pattern = '/' . $pattern . '/';
      // Check if it matches.
      if (preg_match($testing_pattern, $text)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * A list of whitelisted patterns.
   *
   * @return array
   *   The list of patterns.
   */
  private function getPatterns() {
    return [
      'facebook.com\/(.*)\/videos\/(.*)',
      'facebook.com\/(.*)\/photos\/(.*)',
      'facebook.com\/(.*)\/posts\/(.*)',
      'flickr.com\/photos\/(.*)',
      'flic.kr\/p\/(.*)',
      'instagram.com\/p\/(.*)',
      'open.spotify.com\/track\/(.*)',
      'twitter.com\/(.*)\/status\/(.*)',
      'vimeo.com\/\d{7,9}',
      'youtube.com\/watch[?]v=(.*)',
      'youtu.be\/(.*)',
      'ted.com\/talks\/(.*)',
    ];
  }

}
