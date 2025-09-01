<?php

namespace Drupal\Tests\eca_autopost_facebook\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\eca\Plugin\DataType\DataTransferObject;

/**
 * Kernel tests for the eca_autopost_facebook module.
 *
 * @group eca
 * @group eca_cache
 */
class FbPostTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'eca',
    'eca_facebook_post',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(static::$modules);
  }

  /**
   * Tests actions of eca_cache.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testFbPostActions(): void {
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
     *
     */
    $this->assertTrue($result instanceof DataTransferObject);

  }

}
