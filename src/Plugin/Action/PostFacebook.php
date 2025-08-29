<?php

namespace Drupal\eca_autopost_facebook\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Switch current account.
 *
 * @Action(
 *   id = "eca_post_facebook",
 *   label = @Translation("Content: Post Facebook")
 * )
 */
class PostFacebook extends ConfigurableActionBase {

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected AccountSwitcherInterface $accountSwitcher;

  /**
   * A flag indicating whether an account switch was done.
   *
   * @var bool
   */
  protected bool $switched = FALSE;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'message' => NULL,
      'message_alternative' => NULL,
      'link' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message to post'),
      '#default_value' => $this->configuration['message'] ?? '[node:summary] ',
      '#description' => $this->t('Accept tokens, as example yo can use [node:summary], [node:title] etc.'),
      '#weight' => 10,
    ];
    $form['message_alternative'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alternative message to post'),
      '#default_value' => $this->configuration['message_alternative'] ?? '[node:title]',
      '#description' => $this->t(
        'This field is used when the message to be published is empty, it is
        useful for cases in which conditional behavior is needed when the main
        field is not available. Accept tokens, as example yo can use
        [node:summary] or [node:title].
        This message will be used if the first one is empty.'
      ),
      '#weight' => 20,
    ];
    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attached link'),
      '#default_value' => $this->configuration['link'] ?? '[node:url]',
      '#description' => $this->t('Accept tokens, as example yo can use [node:url].'),
      '#weight' => 30,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['link'] = $form_state->getValue('link');
    $this->configuration['message'] = $form_state->getValue('message');
    $this->configuration['message_alternative'] = $form_state->getValue('message_alternative');
    parent::submitConfigurationForm($form, $form_state);
  }

  public function execute() {
    $tokenServices = \Drupal::service('token');
    $content_source = $this->configuration['link'] . $this->configuration['message'];
    if (empty($content_source)) {
      return;
    }

    try {
     $link = (string) $tokenServices->replaceClear($this->configuration['link']);
     $message = (string) $tokenServices->replaceClear($this->configuration['message']);
     if (empty($message) && !empty($this->configuration['message_alternative'])) {
       $message = (string) $tokenServices->replaceClear($this->configuration['message_alternative']);
     }
    }
    catch (\Exception $e) {
      \Drupal::logger('eca_post_facebook')->error('An error occurred while trying to replace tokens: ' . $e->getMessage());
      return FALSE;
    }

    if (empty($message) && empty($link)) {
      \Drupal::logger('eca_post_facebook')->warning('The message to post is empty.');
      return FALSE;
    }

    // Obtiene el access_token y el page_id desde el estado de Drupal.
    $user_token = \Drupal::state()->get('eca_autopost_facebook.page_access_token', '');
    $page_id = \Drupal::state()->get('eca_autopost_facebook.page_id', '');
    $api_version = \Drupal::config('eca_autopost_facebook.settings')->get('api_version') ?? 'v23.0';
    // Si no hay token o page_id, se sale de la función.
    if (empty($user_token) || empty($page_id)) {
      \Drupal::logger('eca_post_facebook')->warning('Empty Access token or Page ID, skipping post.');
      return FALSE;
    }

    // URL de la API de Facebook.
    $url = 'https://graph.facebook.com/' . $api_version . '/' . $page_id . '/feed';

    // Datos que vamos a enviar en el POST.

    // 'message' => $value,

    try {

      $client = \Drupal::httpClient();
      $user_data = $client->get('https://graph.facebook.com/'. $api_version .'/me/accounts', [
        'query' => [
          'access_token' => $user_token,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ]
      ]);
      $decode_user_data = json_decode($user_data->getBody()->getContents(), TRUE);
      $page_access_token = '';
      if (!empty($decode_user_data['data'])) {
        foreach ($decode_user_data['data'] as $page) {
          if ($page['id'] === $page_id) {
            $page_access_token = $page['access_token'];
            break;
          }
        }
      }
      if (empty($page_access_token)) {
        \Drupal::logger('eca_post_facebook')->warning('No se pudo obtener el token de acceso de la página.');
        return FALSE;
      }

      // Realiza el POST usando el servicio http_client de Drupal.
      $response = $client->post($url, [
        'form_params' => [
          'message' => $message,
          'link' => $url,
          'access_token' => $page_access_token,
        ],
      ]);

      // Decodifica la respuesta.
      $body = json_decode($response->getBody()->getContents(), TRUE);

      // Verifica si se recibió el post_id.
      $post_id = $body['id'] ?? FALSE;
      if ($post_id) {
        \Drupal::logger('eca_post_facebook')->info('Post creado con éxito. Post ID: ' . $post_id);
        return $post_id;
      }
      else {
        \Drupal::logger('eca_post_facebook')->warning('Error al crear el post.');
        return FALSE;
      }
    }
    catch (RequestException $e) {
      $responseBody = $e->getResponse()->getBody()->getContents();
      \Drupal::logger('eca_post_facebook')->warning('Error en la solicitud: ' . $e->getMessage());
      return FALSE;
    }
  }

}