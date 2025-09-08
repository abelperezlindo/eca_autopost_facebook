<?php

namespace Drupal\eca_autopost_facebook\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class for config form.
 */
class ConfigForm extends ConfigFormBase {
  /**
   * For use the Drupal state api.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The interface for Config Factory.
   * @param \Drupal\Core\State\State $state
   *   The object State.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    State $state,
    MessengerInterface $messenger
    ) {
    parent::__construct($config_factory, $typedConfigManager);

    $this->state = $state;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('state'),
      $container->get('messenger')
    );
  }

  /**
   * Get Form Id Method.
   */
  public function getFormId() {
    return 'eca_autopost_facebook';
  }

  /**
   * Get Editable Config Names Method.
   */
  public function getEditableConfigNames() {
    return [
      'eca_autopost_facebook.settings',
    ];
  }

  /**
   * Build Form method.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $module_name = 'eca_autopost_facebook';
    $config      = $this->config($module_name . '.settings');
    $token       = $this->state->get($module_name . '.page_access_token', '');
    $page_id     = $this->state->get($module_name . '.page_id', '');
    $api_version = $config->get('api_version') ?? 'v23.0';
    $form        = parent::buildForm($form, $form_state);

    $form['sections'] = [
      '#type'         => 'vertical_tabs',
      '#title'        => $this->t('Settings'),
      '#default_tab'  => 'edit-content-box',
    ];

    $form['content_box'] = [
      '#type'   => 'details',
      '#title'  => $this->t('Facebook API Access Settings'),
      '#group'  => 'sections',
    ];

    $form['content_box']['api_version'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Facebook API Version'),
      '#description'    => $this->t('Use the version specified in the Facebook APP.'),
      '#default_value'  => $api_version,
    ];

    $form['content_box']['page_id'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Facebook page id'),
      '#description'    => $this->t(
        'Facebook page id. '),
      '#default_value'  => $page_id,
    ];

    $form['content_box']['page_access_token'] = [
      '#type'           => 'textarea',
      '#title'          => $this->t('Facebook Page access token'),
      '#description'    => $this->t(
        'Enter the user access token with administrator permissions for the page.
        You can use a short-lived token for testing, but keep in mind that it
        will expire quickly. It is strongly recommended to use a long-lived
        token to avoid service interruptions.
        To obtain a long-lived token on Facebook, you first need to generate a
        short-lived token through the <a href="@explorer" target="_blank"
        rel="noopener">Graph Explorer</a> and then exchange it for a long-lived
        one by making a request to the server with your App ID, App Secret, and
        the previously obtained short-lived token.
        You can find more information <a href="@fbdocs"
        target="_blank" rel="noopener">here</a>.',
        [
          '@explorer' => 'https://developers.facebook.com/tools/explorer/',
          '@fbdocs' => 'https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived'
        ]
      ),
      '#default_value'  => $token,
    ];

    return $form;
  }

  /**
   * Form submit.
   *
   * { @inheritDoc }
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $module_name = 'eca_autopost_facebook';

    $this->state->set(
      $module_name . '.page_id',
      $form_state->getValue('page_id')
    );
    $this->state->set(
      $module_name . '.page_access_token',
      $form_state->getValue('page_access_token')
    );

    $config = $this->config($module_name . '.settings');
    $config->set('api_version', $form_state->getValue('api_version'));
    $config->save();

    $this->messenger->addStatus($this->t('Facebook API settings saved.'));
    return parent::submitForm($form, $form_state);
  }
}
