<?php

namespace Drupal\social_embed\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\social_embed\Service\EmbedHelper;
use Drupal\url_embed\UrlEmbed;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Social Embed endpoint handling.
 */
class EmbedController extends ControllerBase {

  /**
   * @var \Drupal\social_embed\Service\EmbedHelper
   */
  protected EmbedHelper $embedHelper;

  /**
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * @var \Drupal\url_embed\UrlEmbed
   */
  protected UrlEmbed $urlEmbed;

  /**
   * The ModalFormExampleController constructor.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   * @param \Drupal\social_embed\Service\EmbedHelper $embed_helper
   */
  public function __construct(UserDataInterface $user_data, UrlEmbed $url_embed, EmbedHelper $embed_helper) {
    $this->userData = $user_data;
    $this->urlEmbed = $url_embed;
    $this->embedHelper = $embed_helper;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data'),
      $container->get('url_embed'),
      $container->get('social_embed.helper_service')
    );
  }

  /**
   *
   */
  public function checkConsent(string $provider) {
    /** @var \Drupal\social_user\Entity\User $account */
    $account = $this->currentUser();
    $user_states = $this->userData->get('social_embed', $account->id(), 'consent');

    return $user_states[$provider];
  }

  /**
   * Generates embed content of a give URL.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function generateEmbed(Request $request) {
    $url = $request->query->get('url');
    if ($url == NULL) {
      throw new NotFoundHttpException();
    }
    $response = new AjaxResponse();
    $selector = '#social-embed-iframe';
    if ($info = \Drupal::service('url_embed')->getUrlInfo($url)) {
      $iframe = $info['code'];
      ;
      $content = "<div id='social-embed-iframe'><p>$iframe</p></div>";
    }
    else {
      $link = Link::fromTextAndUrl($url, $url)->toString();
      $content = "<div id='social-embed-iframe'><p>$link</p></div>";
    }
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

  /**
   * Returns the placeholder file url.
   */
  public function getPlaceholderImage(): string {
    return $this->embedHelper->getEmbedPlaceholderImage();
  }

}
