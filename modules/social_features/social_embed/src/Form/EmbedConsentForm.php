<?php

namespace Drupal\social_embed\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form for different setting about embed consent.
 */
class EmbedConsentForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'social_embed.settings';

  /**
   * The cache services.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cacheRender;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheRender
   *   The cache services.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cacheRender) {
    parent::__construct($config_factory);
    $this->cacheRender = $cacheRender;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.render'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_embed_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // Add an introduction text to explain what can be done here.
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The following setting will allow the users to be able to provide consent before showing the embedded content. The users will also be able to customize options to allow/disallow embed providers in the user settings.'),
    ];

    $form['consent'] = [
      '#type' => 'details',
      '#title' => $this->t('Site wide consent'),
      '#open' => TRUE,
    ];

    $form['consent']['settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow consent'),
      '#default_value' => $config->get('settings'),
      '#description' => $this->t('When disabled, all the embedded content will be shown by default.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $config = $this->configFactory->getEditable(static::SETTINGS);
    if ($config->get('settings') != ($new_value = $form_state->getValue('settings'))) {
      $this->cacheRender->invalidateAll();
      // Set the submitted configuration setting.
      $config->set('settings', $new_value)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
