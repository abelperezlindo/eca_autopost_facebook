<?php

namespace Drupal\Tests\eca_autopost_facebook\Kernel;

use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Kernel tests for the eca_autopost_facebook module.
 *
 * @group eca
 * @group eca_autopost_facebook
 */
class FbPostTest extends KernelTestBase {

  /**
   * Mock client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $mockClient;

  /**
   * History of requests/responses.
   *
   * @var array
   */
  protected $history = [];


  /**
   * Mocks the http-client.
   */
  protected function mockClient(Response ...$responses) {
    if (!isset($this->mockClient)) {
      // Create a mock and queue responses.
      $mock = new MockHandler($responses);

      $handler_stack = HandlerStack::create($mock);
      $history = Middleware::history($this->history);
      $handler_stack->push($history);
      $this->mockClient = new Client(['handler' => $handler_stack]);
    }
    $this->container->set('http_client', $this->mockClient);
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_autopost_facebook',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(static::$modules);
    \Drupal::state()->set('eca_autopost_facebook.page_id', '10000000');
    \Drupal::state()->set('eca_autopost_facebook.page_access_token', 'faketoken123');
    $config = \Drupal::configFactory()
      ->getEditable('eca_autopost_facebook.settings');
    $config->set('api_version', 'v50.0');
    $config->save();
  }

  /**
   * Tests actions of eca_autopost_facebook.
   *
   * See this example for mocking http client in kernel tests:
   * https://git.drupalcode.org/project/build_hooks/-/blob/8.x-2.x/tests/src/Kernel/BuildHooksKernelTestBase.php#L91
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testFbPostActions(): void {

    $this->mockClient(
    new Response('200', [], json_encode([
      'id' => '1234567890',
    ])));

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');

    /** @var \Drupal\eca_autopost_facebook\Plugin\Action\PostFacebook $action */
    $action = $action_manager->createInstance('eca_post_facebook', [
      'message' => 'Hello',
      'message_alternative' => '',
      'link' => '',
    ]);
    $result = $action->execute();

    /**
     * Who to make assertion here? TODO
     */
    $this->assertTrue(!$result);

  }

}
