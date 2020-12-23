<?php

/**
 * @file
 * The form inc file for the Social base theme.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * Implements template_preprocess_form().
 */
function socialbase_preprocess_form(&$variables) {

  $element = $variables['element'];

  // If this is a search content form set a variable for twig.
  if ($element['#form_id'] === 'search_content_form' || $element['#form_id'] === 'search_hero_form' || $element['#form_id'] === 'geolocation_search_form' || $element['#form_id'] === 'geolocation_search_content_form') {
    $variables['is_search_form'] = TRUE;
  }

  // We have set the region via propress block, now pass this variable on
  // to be used in twig. In twig we can distinguish the nav bar form from
  // the hero form as they both have the same ID's.
  if (isset($element['#region'])) {
    if ($element['#region'] === 'hero') {
      $variables['in_hero_region'] = TRUE;
    }

    if ($element['#region'] === 'content-top') {
      $variables['in_content_top_region'] = TRUE;
    }
  }

  $variables['attributes']['class'][] = 'clearfix';

  if ($element['#form_id'] === 'comment_comment_form' || $element['#form_id'] === 'comment_post_comment_form' || $element['#form_id'] === 'private_message_add_form') {
    $current_user = \Drupal::currentUser();
    if ($current_user) {
      $storage = \Drupal::entityTypeManager()->getStorage('profile');
      if (!empty($storage)) {
        $user_profile = $storage->loadByUser($current_user, 'profile');
        if ($user_profile) {
          $content = \Drupal::entityTypeManager()
            ->getViewBuilder('profile')
            ->view($user_profile, 'compact');
          $variables['current_user_picture'] = $content;
          $variables['comment_wrapper'] = new Attribute();
          $variables['comment_reply_form_wrapper'] = new Attribute();
          // Used for example for ajax comments in form--comment.html.twig, see
          // Drupal\social_ajax_comments\Controller::socialCancel().
          if (!empty($element['form_html_id']['#value'])) {
            $variables['comment_wrapper']->addClass('social_' . $element['form_html_id']['#value']);
            // Only on reply ajax call add another class and wrapper.
            // we need this class so ajax_comments understand to remove this
            // element so there wont be multiple reply forms opened.
            if (!empty($element['#action']) && strpos($element['#action'], 'ajax_comments/reply') !== FALSE) {
              $variables['comment_reply_form_wrapper']->addClass('ajax-comments-form-reply');
              $variables['comment_reply_form_wrapper']->addClass('comments');
              $variables['comment_reply_form_wrapper']->addClass('social_reply_form_wrapper_' . $element['form_html_id']['#value']);
            }
          }
        }
      }
    }

    // Comment edit form.
    if (\Drupal::routeMatch()->getRouteName() === 'entity.comment.edit_form') {
      $comment = \Drupal::routeMatch()->getParameter('comment');

      if (is_object($comment)) {
        // Display comment created date in format 'time ago'.
        $created_time_ago = \Drupal::service('date.formatter')
          ->formatTimeDiffSince($comment->getCreatedTime(), ['granularity' => 1, 'return_as_object' => TRUE]);

        $submitted = t('@time ago', ['@time' => $created_time_ago->getString()]);
        $variables['submitted'] = Link::fromTextAndUrl($submitted, $comment->urlInfo('canonical'));
        $variables['#cache']['max-age'] = $created_time_ago->getMaxAge();

        // Display author information.
        $account = $comment->getOwner();
        if ($account) {
          // Author profile picture.
          $storage = \Drupal::entityTypeManager()->getStorage('profile');
          if (!empty($storage)) {
            $user_profile = $storage->loadByUser($account, 'profile');
            if ($user_profile) {
              $content = \Drupal::entityTypeManager()
                ->getViewBuilder('profile')
                ->view($user_profile, 'compact');
              $variables['author_picture'] = $content;
            }
          }

          // Author name.
          $username = [
            '#theme' => 'username',
            '#account' => $account,
          ];
          $variables['author'] = drupal_render($username);
        }
      }

    }

  }

  if ($element['#form_id'] === 'social_post_entity_form') {

    if (\Drupal::routeMatch()->getRouteName() === 'entity.post.edit_form') {
      $post_id = $element['#post_id'];
      /** @var \Drupal\social_post\Entity\Post $post */
      $post = entity_load('post', $post_id);

      // Display post created date in format 'time ago'.
      $created_time_ago = \Drupal::service('date.formatter')
        ->formatTimeDiffSince($post->getCreatedTime(), ['granularity' => 1, 'return_as_object' => TRUE]);
      $date = t('%time ago', ['%time' => $created_time_ago->getString()]);
      $variables['date']['#markup'] = $date;
      $variables['#cache']['max-age'] = $created_time_ago->getMaxAge();

      // To change user picture settings (e.g. image style), edit the 'compact'
      // view mode on the User entity. Note that the 'compact' view mode might
      // not be configured, so remember to always check the theme setting first.
      $account = $post->getOwner();
      if ($account) {
        $author_name = $account->getDisplayName();
        $variables['author_name']['#markup'] = $author_name;

        $storage = \Drupal::entityTypeManager()->getStorage('profile');
        if (!empty($storage)) {
          $user_profile = $storage->loadByUser($account, 'profile');
          if ($user_profile) {
            $content = \Drupal::entityTypeManager()
              ->getViewBuilder('profile')
              ->view($user_profile, 'compact');
            $variables['author_picture'] = $content;
          }
        }
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_social_post_entity_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['actions']['#inline'] = TRUE;
  $form['field_visibility']['#inline'] = TRUE;

  // Give the `delete` button correct classes.
  if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] == 'link') {
    $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_comment_post_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  if (isset($form['actions']['submit']['#button_type'])) {
    $form['actions']['submit']['#button_type'] = 'primary';
    $form['actions']['submit']['#comment_button'] = TRUE;
  }

  // Set a placeholder text to guide the user.
  $form['field_comment_body']['widget'][0]['#placeholder'] = t('Write a comment...');

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_private_message_add_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  foreach ($form['actions'] as $key => &$actions) {
    // Must be pretty sure it's a submit button.
    if (is_array($actions) && isset($actions['#submit']) && is_array($actions['#submit'])) {
      // Key must have submit in it.
      if (strpos($key, 'submit-') !== FALSE) {
        $actions['#button_type'] = 'flat';
        $actions['#comment_button'] = TRUE;
      }
    }
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_comment_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['field_comment_body']['widget'][0]['#title_display'] = 'invisible';
  if (isset($form['actions']['submit']['#button_type'])) {
    $form['actions']['submit']['#button_type'] = 'primary';
    $form['actions']['submit']['#comment_button'] = TRUE;
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_node_event_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['field_event_address'])) {
    $form['field_event_address']['widget'][0]['#title'] = '';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_profile_profile_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (isset($form['field_profile_address'])) {
    $form['field_profile_address']['widget'][0]['#title'] = '';
  }
}

/**
 * Implements hook_form_user_form_alter().
 */
function socialbase_form_user_form_alter(&$form) {
  if (isset($form['email_notifications'])) {
    $form['#attached']['library'][] = 'socialbase/form-horizontal';
    $form['timezone']['#attributes']['class'][] = 'form-horizontal';
  }
}

/**
 * Implements hook_form_alter().
 */
function socialbase_form_alter(&$form, FormStateInterface $form_state, $form_id) {

  // Client side validation is not consistent accross devices. Disable for now.
  if (!isset($form['#attributes']['novalidate'])) {
    $form['#attributes']['novalidate'] = 'novalidate';
  }

  // In the code above we disable HTML5 native validation,
  // for the following forms enable validating.
  // These forms only have required attributes.
  $validate_forms = [
    'private_message_add_form',
    'comment_post_comment_form',
    'social_post_entity_form',
  ];

  if (in_array($form_id, $validate_forms)) {
    unset($form['#attributes']['novalidate']);
  }

  $social_group_types = [
    'open_group',
    'closed_group',
    'public_group',
  ];
  \Drupal::moduleHandler()->alter('social_group_types', $social_group_types);

  $group_add_card_to_forms = [];
  $group_confirm_forms = [];
  $group_default_forms = [];
  $content_reporting_forms = [];

  foreach ($social_group_types as $social_group_type) {
    $group_add_card_to_forms[] = "group_content_{$social_group_type}-group_membership_group-join_form";
    $group_add_card_to_forms[] = "group_content_{$social_group_type}-group_membership_add_form";
    $group_add_card_to_forms[] = "group_content_{$social_group_type}-group_membership_edit_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_membership_group-leave_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_membership_delete_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_membership_delete_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_invitation_delete_form";
    $group_confirm_forms[] = "group_content_{$social_group_type}-group_invitation_add_form";
    $group_confirm_forms[] = "bulk_group_invitation";
    $group_confirm_forms[] = "bulk_group_invitation_confirm";
    $group_confirm_forms[] = "group_{$social_group_type}_delete_form";
    $group_default_forms[] = "group_{$social_group_type}_add_form";
    $group_default_forms[] = "group_{$social_group_type}_edit_form";
  }

  // Add card classes to all the social_content_reporting forms if enabled.
  try {
    // Get all 'report_' flags.
    $report_types = \Drupal::service('social_content_report.content_report_service')->getReportFlagTypes();
    foreach ($report_types as $report_type) {
      $content_reporting_forms[] = 'flagging_' . $report_type . '_add_form';
      $content_reporting_forms[] = 'flagging_' . $report_type . '_edit_form';
    }
  }
  // If the service doesn't exist then we don't need to do anything.
  catch (ServiceNotFoundException $e) {
  }

  // These forms have a container with class form-action at the bottom and
  // we style it in a way that the primary/submit button is aligned right
  // Also we define the style of the buttons based on their function.
  $default_forms = array_merge(
    $group_add_card_to_forms,
    $group_confirm_forms,
    $group_default_forms,
    $content_reporting_forms,
    [
      'user_form',
      'user_pass_reset',
      'social_user_login_form',
      'user_register_form',
      'profile_profile_add_form',
      'profile_profile_edit_form',
      'social_user_password_form',
      'user_cancel_form',
      'social_group_add',
      'node_event_form',
      'node_topic_form',
      'comment_comment_delete_form',
      'comment_post_comment_delete_form',
      'comment_comment_delete_form',
      'post_delete_form',
      'post_photo_delete_form',
      'node_event_delete_form',
      'node_topic_delete_form',
      'profile_profile_delete_form',
      'private_message_thread_delete_form',
      'data_policy_edit_form',
      'data_policy_data_policy_agreement',
      'data_policy_data_policy_revision_edit',
      'event_an_enroll_form',
      'block_content_custom_content_list_form',
      'block_content_custom_content_list_edit_form',
      'bulk_group_invitation',
      'social_bulk_group_invitation',
      'enroll_invite_email_form',
      'enroll_invite_user_form',
    ]
  );

  if (in_array($form_id, $group_default_forms, TRUE) && theme_get_setting('content_entity_form_style') === 'open_social') {
    _socialbase_update_content_entity_forms($form, $form_state);
  }

  if (in_array($form_id, $default_forms)) {
    $form['#attributes']['class'][] = 'form--default';

    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#button_type'] = 'primary';
      $form['actions']['submit']['#button_level'] = 'raised';
    }

    if (isset($form['actions']['delete'])) {

      // Some `delete` buttons are not inputs but links.
      if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] == 'link') {
        $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';
      }
      else {
        $form['actions']['delete']['#button_type'] = 'flat';
      }

    }
    if (isset($form['actions']['cancel'])) {

      // Some `cancel` buttons are not inputs but links.
      if (isset($form['actions']['cancel']['#type']) &&  $form['actions']['cancel']['#type'] == 'link') {
        $form['actions']['cancel']['#attributes']['class'][] = 'btn btn-flat';
      }
      else {
        $form['actions']['cancel']['#button_type'] = 'flat';
      }
    }
  }

  // Here we list all the forms that need to be wrapped in cards.
  $add_card_to_forms = array_merge(
    $group_add_card_to_forms,
    [
      'event_an_enroll_form',
      'data_policy_edit_form',
      'data_policy_data_policy_agreement',
      'data_policy_data_policy_revision_edit',
      'views_bulk_operations_configure_action',
      'views_bulk_operations_confirm_action',
      'bulk_group_invitation',
      'enroll_invite_user_form',
    ]
  );

  if (in_array($form_id, $add_card_to_forms)) {
    $form['#attributes']['class'][] = 'card';
    $form['actions']['#prefix'] = '</div></div>';
  }

  // The content reporting form also needs a card display except when rendered
  // in a dialog.
  if (in_array($form_id, $content_reporting_forms) && !\Drupal::request()->isXmlHttpRequest()) {
    $form['#attributes']['class'][] = 'card';
    $form['actions']['#prefix'] = '</div></div>';
  }

  if (\Drupal::routeMatch()->getRouteName() === 'private_message.private_message_create' && $form_id == 'private_message_add_form') {
    $form['#attributes']['class'][] = 'card';
    $form['#attributes']['class'][] = 'form--default';
    $form['actions']['#prefix'] = '</div></div>';

    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#button_type'] = 'primary';
      $form['actions']['submit']['#button_level'] = 'raised';
    }
  }

  // List all the confirmation forms here. We need to close the card
  // before showing the actions.
  $confirm_forms = array_merge($group_confirm_forms, [
    'user_cancel_form',
    'post_delete_form',
    'post_photo_delete_form',
    'node_event_delete_form',
    'node_topic_delete_form',
    'comment_comment_delete_form',
    'comment_post_comment_delete_form',
    '_auth_login_notice_form',
    'private_message_thread_delete_form',
    'profile_profile_delete_form',
  ]);

  if (in_array($form_id, $confirm_forms)) {
    $form['description']['#prefix'] = '<div class="clearfix">';
    $form['description']['#suffix'] = '</div></div></div>';
  }

  // Add extra classes to.
  if ($form_id == 'enroll_action_form') {
    $form['enroll_for_this_event']['#button_type'] = 'accent';
    $form['enroll_for_this_event']['#attributes']['class'][] = 'btn-lg';
  }

  // Ensure the sign-up or login link is displayed as a nested card section.
  // Bootstrap already converted the fieldset itself to a card.
  if ($form_id === 'social_user_login_form') {
    $form['username_login']['sign-up-link']['#prefix'] = '<div class="card__nested-section">';
    $form['username_login']['sign-up-link']['#suffix'] = '</div>';
  }
  elseif ($form_id === 'user_register_form') {
    $form['account']['login-link']['#prefix'] = '<div class="card__nested-section">';
    $form['account']['login-link']['#suffix'] = '</div>';
  }
  elseif ($form_id === 'social_user_password_form') {
    $form['forgot']['sign-up-link']['#prefix'] = '<div class="card__nested-section">';
    $form['forgot']['sign-up-link']['#suffix'] = '</div>';
  }
}

/**
 * Implements hook_form_BASE_ID_alter().
 *
 * Set button types for input elements.
 * Note that these variables are read in hook_preprocess_input.
 */
function socialbase_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $form['#attributes']['class'][] = 'form--default';

  // Delete is not an input element, so we must apply extra classes
  // to anchor directly.
  $button_size = theme_get_setting('button_size');
  if (isset($form['actions']['delete'])) {
    $form['actions']['delete']['#attributes']['class'][] = 'btn btn-flat';

    if ($button_size === 'btn-xs') {
      $form['actions']['delete']['#attributes']['class'][] = 'btn-xs';
    }
    elseif ($button_size === 'btn-sm') {
      $form['actions']['delete']['#attributes']['class'][] = 'btn-sm';
    }
    elseif ($button_size === 'btn-lg') {
      $form['actions']['delete']['#attributes']['class'][] = 'btn-lg';
    }
  }

  // Unlock is not an input element, so we must apply extra classes
  // to anchor directly.
  if (isset($form['actions']['unlock'])) {
    $form['actions']['unlock']['#attributes']['class'][] = 'btn';
    $form['actions']['unlock']['#attributes']['class'][] = 'btn-flat';
  }

  if (isset($form['actions']['preview'])) {
    $form['actions']['preview']['#button_type'] = 'flat';
  }
  if (isset($form['actions']['submit'])) {
    $form['actions']['submit']['#button_level'] = 'raised';
    $form['actions']['submit']['#button_type'] = 'primary';
  }
  if (isset($form['actions']['publish'])) {
    $form['actions']['publish']['#button_level'] = 'raised';
    $form['actions']['publish']['#button_type'] = 'primary';
  }
  if (isset($form['actions']['unpublish'])) {
    $form['actions']['unpublish']['#button_level'] = 'raised';
    $form['actions']['unpublish']['#button_type'] = 'primary';
  }

  // Update the node forms according to #3186003.
  if (theme_get_setting('content_entity_form_style') === 'open_social') {
    _socialbase_update_content_entity_forms($form, $form_state);
  }

}

/**
 * Implements hook_form_search_content_form_alter().
 */
function socialbase_form_search_content_form_alter(&$form) {
  $form['actions']['submit']['#icon'] = [
    '#type' => "inline_template",
    '#template' => '<svg class="icon--search"><use xlink:href="#icon-search"></use></svg>',
  ];
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function socialbase_form_node_layout_builder_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Make the layout builder specific styling available.
  $form['#attached']['library'][] = 'socialbase/form--toolbar';
}

/**
 * Updates any content entity form with the new #3186003 content.
 *
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 */
function _socialbase_update_content_entity_forms(array &$form, FormStateInterface $form_state) {
  // Let's remove the form elements not needed by Open Social.
  // We understand this is quite BC breaking, url_redirects / revision info
  // we don't use as Open Social, you might use. So by
  // changing the theme setting in social blue, you can go back to the
  // old settings and use Field UI to change the other fields.
  if (theme_get_setting('content_entity_form_style') === 'open_social') {
    // We want to move all the fields from $form['advanced'] to our new
    // field sets which are created as part of #3186003.
    if (!empty($form['advanced'])) {
      unset($form['advanced']);
    }

    // Unset optional fields.
    if (isset($form['revision'])) {
      unset($form['revision']);
    }

    if (isset($form['revision_log'])) {
      unset($form['revision_log']);
    }

    if (isset($form['revision_information'])) {
      unset($form['revision_information']);
    }

    if (isset($form['url_redirects'])) {
      unset($form['url_redirects']);
    }

    if (isset($form['meta'])) {
      unset($form['meta']);
    }

    // Add descriptive text to URL Alias field.
    if (isset($form['path']['widget'][0]['alias'])) {
      $form['path']['widget'][0]['alias']['#description'] = t('The URL alias allows you to customise the link to this page.');
    }

    // Hide the automatic URL alias.
    if (isset($form['path']['widget'][0]['pathauto'])) {
      $form['path']['widget'][0]['pathauto']['#type'] = 'hidden';
      // Path uses the above checkbox to decide whether or not to save
      // the automatically generated alias, or use a custom one.
      // since we hide the element, and the default is checked,
      // the path will never be overridden if someone updates it.
      // So we need to check this in our custom submit handler.
      // It should perform before path auto runs.
      array_unshift($form['actions']['submit']['#submit'], '_socialbase_path_widget_submit');
    }

    // Move the fields from advanced to group_settings.
    if (!empty($form['#fieldgroups']['group_settings'])) {
      // Move the book outline fields to the group settings.
      // #502430 we need to remove this ourselves, users with permission
      // can see the book outline even when not configured as part
      // of the book settings.
      if (!empty($form['book']) &&
        $form_state->getFormObject() !== NULL &&
        \Drupal::moduleHandler()->moduleExists('book')) {
        // Grab the Entity to see if books are enabled for this one.
        $entity = $form_state->getFormObject()->getEntity();
        if (method_exists($entity, 'getType') && !empty($entity->getType()) && is_string($entity->getType())) {
          // Move it to our new fieldset on book pages either on its own
          // or in the group_settings.
          $form['book']['#type'] = 'fieldset';
          $form['book']['#attributes']['class'][] = 'card';
          $form['book']['#weight'] = isset($form['#fieldgroups']['group_visibility']->weight) ? $form['#fieldgroups']['group_visibility']->weight + 1 : '3';
          $enabled = book_type_is_allowed($entity->getType());
          // If it's not enabled we can just safely unset it.
          if (!$enabled) {
            unset($form['book']);
          }
        }
      }

      // Move the node Menu settings to group_settings.
      if (!empty($form['menu'])) {
        $form['menu']['#group'] = 'group_settings';
        $form['menu']['#weight'] = -20;
        $form['menu']['#title'] = t('Menu link');
        if (!empty($form['menu']['link']['title'])) {
          $form['menu']['link']['title']['#title'] = t('Title');
          $form['menu']['link']['title']['#description'] = t('This title will be displayed in the menu.');
        }
        if (!empty($form['menu']['link']['description'])) {
          $form['menu']['link']['title']['#description'] = t('This description will be displayed when hovering over the menu link.');
        }
      }

      // Move the Published status to group settings with a new wrapper.
      if (!empty($form['status'])) {
        // Add an details element to status.
        $form['status_label'] = [
          '#type' => 'details',
          '#title' => t('Publish status'),
          '#description' => '',
          '#open' => TRUE,
          '#weight' => 110,
          '#group' => 'group_settings',
        ];
        $form['status']['#group'] = 'status_label';
      }

      // Node author information for administrators.
      if (isset($form['uid']) || isset($form['created'])) {
        $form['author_info'] = [
          '#type' => 'details',
          '#title' => t('Author Information'),
          '#description' => '',
          '#open' => TRUE,
          '#weight' => 100,
          '#group' => 'group_settings',
        ];
      }

      // Move author uid to the new author info fieldset,
      // and remove descriptive text.
      if (isset($form['uid'])) {
        $form['uid']['#group'] = 'author_info';
        $form['uid']['widget']['#description'] = '';
        if (!empty($form['uid']['widget'][0]['target_id'])) {
          $form['uid']['widget'][0]['target_id']['#description'] = '';
        }
        $form['author_info']['uid'] = $form['uid'];
        unset($form['uid']);
      }

      // Move created to the new author info fieldset,
      // and remove descriptive text.
      if (isset($form['created'])) {
        $form['created']['#group'] = 'author_info';
        $form['created']['widget']['#description'] = '';
        $form['created']['widget'][0]['#description'] = '';
        $form['created']['widget'][0]['value']['#description'] = '';
        $form['author_info']['created'] = $form['created'];
        unset($form['created']);
      }
    }

    // According to FileWidget.php we cant use a widget form alter.
    if (!empty($form['field_files']) && !empty($form['field_files']['widget']['#file_upload_title'])) {
      $form['field_files']['widget']['#file_upload_title'] = t('Attachments');
    }

    // Change the button text from Save to Create topic / event etc.
    // Adds a cancel button.
    // Removes the preview button.
    if (!empty($form['actions']['submit']) &&
      !empty($form['actions']['submit']['#value']) &&
      $form_state->getFormObject() !== NULL &&
      method_exists($form_state->getFormObject(), 'getOperation')) {

      // Change the button text from Save to Create topic / event etc.
      // Only on node add pages.
      if ($form_state->getFormObject()->getOperation() !== 'edit') {
        $entity = $form_state->getFormObject()->getEntity();
        if (method_exists($entity, 'getType') && !empty($entity->getType()) && is_string($entity->getType())) {
          $form['actions']['submit']['#value'] = t('Create @entity', ['@entity' => $entity->getType()]);
        }
        if (method_exists($entity, 'getGroupType') && !empty($entity->getGroupType()) && is_string($entity->getGroupType()->label())) {
          $form['actions']['submit']['#value'] = t('Create @entity', ['@entity' => $entity->getGroupType()->label()]);
        }
      }

      // Adds cancel button.
      if (!isset($form['actions']['cancel'])) {
        $form['actions']['cancel'] = [
          '#type' => 'submit',
          '#value' => t('Cancel'),
          '#submit' => ['_socialbase_entity_cancel'],
          '#limit_validation_errors' => [],
          '#attributes' => [
            'class' => ['button btn-default btn waves-effect waves-btn'],
          ],
        ];
      }

      // Remove the preview button.
      if (isset($form['actions']['preview'])) {
        unset($form['actions']['preview']);
      }
    }
  }
}

/**
 * Custom form submit handler for handling an path alias update.
 *
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function _socialbase_path_widget_submit(array $form, FormStateInterface $form_state) {
  // Path uses the pathauto checkbox to decide whether or not to save
  // the automatically generated alias, or use a custom one.
  // since we hide the element, and the default is checked,
  // the path will never be overridden if someone updates it.
  // So we need to check this in our custom submit handler.
  $default_path = $form['path']['widget'][0]['alias']['#default_value'];
  $input = $form_state->getUserInput();
  $updated_path = isset($input['path'][0]['alias']) ? $input['path'][0]['alias'] : '';
  // If we don't have a match, make sure we override the checkbox as well.
  if ($updated_path !== $default_path) {
    // Override the User input, act as if this was changed.
    $input['path'][0]['pathauto'] = "0";
    $form_state->setUserInput($input);
    // Override the value.
    $form_state->setValue(['path', '0', 'pathauto'], "0");
  }
}

/**
 * Redirects to the canonical page, or home when pressing cancel form.
 *
 * @param array $form
 *   Join and leave form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state interface.
 */
function _socialbase_entity_cancel(array &$form, FormStateInterface $form_state) {
  // By default the front page will be used,
  // especially for adding a node.
  $form_state->setRedirect('<front>');

  // If we have an existing node, we rather go to the node itself.
  if (!empty($form_state->getFormObject()->getEntity())) {
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity instanceof NodeInterface && $entity->id() !== NULL) {
      $form_state->setRedirect(
        'entity.node.canonical',
        ['node' => $entity->id(), []]
      );
    }
    if ($entity instanceof GroupInterface && $entity->id() !== NULL) {
      $form_state->setRedirect(
        'entity.group.canonical',
        ['group' => $entity->id(), []]
      );
    }
  }
}
