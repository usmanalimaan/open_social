<?php

namespace Drupal\social_embed\Controller;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
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
   * The user data services.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * Url Embed services.
   *
   * @var \Drupal\url_embed\UrlEmbed
   */
  protected UrlEmbed $urlEmbed;

  /**
   * The EmbedController constructor.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data services.
   * @param \Drupal\url_embed\UrlEmbed $url_embed
   *   The url embed services.
   */
  public function __construct(UserDataInterface $user_data, UrlEmbed $url_embed) {
    $this->userData = $user_data;
    $this->urlEmbed = $url_embed;
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
      $container->get('url_embed')
    );
  }

  /**
   * Generates embed content of a give URL.
   *
   * When the site-wide setting for consent is enabled, the links in posts and
   * nodes will be replaced with placeholder divs and a show content button.
   *
   * Once user clicks the button, it will send request to this controller along
   * with url of the content to embed and an uuid which differentiates each
   * link.
   *
   * See:
   * 1. SocialEmbedConvertUrlToEmbedFilter::convertUrls
   * 2. SocialEmbedUrlEmbedFilter::process
   * 3. EmbedConsentForm::buildForm
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function generateEmbed(Request $request) {
    // Get the requested URL of content to embed.
    $url = $request->query->get('url');
    // Get unique identifier for the button which was clicked.
    $uuid = $request->query->get('uuid');

    // If $url or $uuid is not present, then request is malformed.
    if ($url == NULL && Uuid::isValid($uuid) != FALSE) {
      throw new NotFoundHttpException();
    }

    // Let's prepare the response.
    $response = new AjaxResponse();

    // Use uuid to set the selector to the specific div we need to replace.
    $selector = "#social-embed-iframe-$uuid";
    // If the content is embeddable then return the iFrame.
    if ($info = $this->urlEmbed->getUrlInfo($url)) {
      $iframe = $info['code'];
      $content = "<div id='social-embed-iframe-$uuid'><p>$iframe</p></div>";
    }
    else {
      // Else return the link itself.
      $content = Link::fromTextAndUrl($url, Url::fromUri($url))->toString();
    }

    // And return the response which will replace the button
    // with embeddable content.
    $response->addCommand(new ReplaceCommand($selector, $content));
    return $response;
  }

}