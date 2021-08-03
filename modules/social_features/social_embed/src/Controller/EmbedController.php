<?php

namespace Drupal\social_embed\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 */
class EmbedController extends ControllerBase {

  /**
   *
   */
  public function checkConsent(string $provider) {
    /** @var \Drupal\social_user\Entity\User $account */
    $account = $this->currentUser();

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');

    $user_states = $user_data->get('social_embed', $account->id(), 'consent');

    return $user_states[$provider];
  }

  /**
   * Cancel handler for the cancel form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function generateEmbed(Request $request) {
//    $json_string = \Drupal::request()->getContent();
//    $decoded = Json::decode($json_string);
//    $url = $decoded['url'];
//    if (!isset($url)) {
//      throw new NotFoundHttpException();
//    }
    //** TODO, CREATE THE URL as parameter, see AjaxCommentsController */
    $info = \Drupal::service('url_embed')->getUrlInfo('https://www.youtube.com/watch?v=VXgLBa5jgr8');
    $iframe = $info['code'];
    $response = new AjaxResponse();
    $selector = '#social-embed-container';
    // $content = "<div class='container' id='social-embed-placeholder' data-attribute=$url><p>$iframe</p></div>";
    $content = "<div id='social-embed-iframe'><p>$iframe</p></div>";
    $response->addCommand(new ReplaceCommand($selector, $content));
    $response->addCommand(new AlertCommand('KAAS'));

    return $response;
  }

  /**
   *
   */
  public function getPlaceholderImage() {

  }

}
