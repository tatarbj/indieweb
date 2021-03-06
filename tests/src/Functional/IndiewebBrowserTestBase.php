<?php

namespace Drupal\Tests\indieweb\Functional;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides the base class for web tests for Indieweb.
 */
abstract class IndiewebBrowserTestBase extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'block',
    'node',
    'indieweb',
    'indieweb_test',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A simple authenticated user.
   *
   * @var
   */
  protected $authUser;

  /**
   * Default title.
   *
   * @var string
   */
  protected $title_text = 'Hello indieweb';

  /**
   * Default body text.
   *
   * @var string
   */
  protected $body_text = 'Getting on the Indieweb is easy. Just install this module!';

  /**
   * Default summary text.
   *
   * @var string
   */
  protected $summary_text = 'A summary';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Use administrator role, less hassle for browsing around.
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);

    // Set front page to custom page instead of /user/login or /user/x
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/indieweb-test-front')
      ->save();
  }

  /**
   * Creates several node types that are useful for micropub, posting etc.
   *
   * @param $types
   *   Only create those types.
   */
  protected function createNodeTypes($types = []) {

    $this->drupalLogin($this->adminUser);

    foreach (['like', 'bookmark', 'repost', 'reply', 'rsvp', 'event'] as $type) {

      if (!empty($types)) {
        if (!in_array($type, $types)) {
          continue;
        }
      }

      $edit = ['name' => $type, 'type' => $type];
      $this->drupalPostForm('admin/structure/types/add', $edit, 'Save and manage fields');

      if ($type != 'event') {
        $edit = ['new_storage_type' => 'link', 'label' => 'Link', 'field_name' => $type . '_link'];
        $this->drupalPostForm('admin/structure/types/manage/' . $type . '/fields/add-field', $edit, 'Save and continue');
        $this->drupalPostForm(NULL, [], 'Save field settings');
        $this->drupalPostForm(NULL, [], 'Save settings');
        $edit = ['fields[field_' . $type . '_link][type]' => 'link_microformat'];
        $this->drupalPostForm('admin/structure/types/manage/' . $type . '/display', $edit, 'Save');
      }

      if ($type == 'rsvp') {
        $edit = ['existing_storage_name' => 'indieweb_rsvp', 'existing_storage_label' => 'RSVP'];
        $this->drupalPostForm('admin/structure/types/manage/' . $type . '/fields/add-field', $edit, 'Save and continue');
        $edit = ['fields[indieweb_rsvp][type]' => 'list_microformat'];
        $this->drupalPostForm('admin/structure/types/manage/' . $type . '/display', $edit, 'Save');
      }

      if ($type == 'event') {
        $edit = ['new_storage_type' => 'daterange', 'label' => 'Date', 'field_name' => 'date'];
        $this->drupalPostForm('admin/structure/types/manage/' . $type . '/fields/add-field', $edit, 'Save and continue');
        $this->drupalPostForm(NULL, [], 'Save field settings');
        $this->drupalPostForm(NULL, [], 'Save settings');
      }
    }

    drupal_flush_all_caches();
  }

  /**
   * Enable webmention functionality in the UI.
   *
   * @param $edit
   */
  protected function enableWebmention($edit = []) {
    $edit += [
      'webmention_enable' => 1,
      'pingback_enable' => 1,
      'webmention_uid' => $this->adminUser->id(),
      'webmention_secret' => 'valid_secret',
      'webmention_endpoint' => 'https://webmention.io/example.com/webmention',
      'pingback_endpoint' => 'https://webmention.io/webmention?forward=http://example.com/webmention/notify',
    ];
    $this->drupalPostForm('admin/config/services/indieweb/webmention', $edit, 'Save configuration');
  }

  /**
   * Creates a page and send webmention to url potentially.
   *
   * @param $target_url
   * @param $publish
   * @param $custom
   */
  protected function createPage($target_url = '', $publish = FALSE, $custom = FALSE, $edit = []) {
    $edit += [
      'title[0][value]' => 'It sure it!',
      'body[0][value]' => 'And here is mine!',
    ];

    if ($publish) {
      $edit['indieweb_syndication_targets[' . $target_url . ']'] = TRUE;
    }

    if ($custom) {
      $edit['indieweb_send_custom_url'] = $target_url;
    }

    $this->drupalPostForm('node/add/page', $edit, 'Save');
  }

  /**
   * Change block configuration.
   *
   * @param $block_id
   *   The block id.
   * @param $edit
   *   The edit
   */
  protected function changeBlockConfiguration($block_id, $edit) {
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/structure/block/manage/' . $block_id, $edit, 'Save block');
    $this->drupalLogout();
  }

  /**
   * Gets a minimum webmention payload.
   *
   * @param $node
   * @param string $secret
   *
   * @return array
   */
  protected function getWebmentionPayload(NodeInterface $node, $secret = 'in_valid_secret') {

    $webmention = [
      'secret' => $secret,
      'source' => 'http://external.com/page/1',
      'target' => '/node/' . $node->id(),
      'post' => [
        'type' => 'entry',
        'wm-property' => 'like-of',
        'content' => [
          'text' => 'Webmention from external.com'
        ],
      ],
    ];

    return $webmention;
  }

  /**
   * Sends a webmention request.
   *
   * @param $post
   * @param $json
   * @param $debug
   *
   * @return int $status_code
   */
  protected function sendWebmentionNotificationRequest($post = [], $json = TRUE, $debug = FALSE) {
    $notify_endpoint = Url::fromRoute('indieweb.webmention.notify', [], ['absolute' => TRUE])->toString();

    $client = \Drupal::httpClient();
    try {
      if ($json) {
        $response = $client->post($notify_endpoint, ['json' => $post]);
      }
      else {
        $response = $client->post($notify_endpoint, ['form_params' => $post]);
      }
      $status_code = $response->getStatusCode();
    }
    catch (\Exception $e) {
      $status_code = 400;
      if (strpos($e->getMessage(), '404 Not Found') !== FALSE) {
        $status_code = 404;
      }
      // Use following line if you want to debug the exception in tests.
      if ($debug) {
        debug($e->getMessage());
      }
    }

    return $status_code;
  }

  /**
   * Sends a micropub request.
   *
   * @param $post
   * @param $access_token
   * @param $debug
   * @param $type
   *   Either POST or JSON (form_params or json)
   * @param $return_array_response
   *
   * @return array|int
   */
  protected function sendMicropubRequest($post, $access_token = 'is_valid', $debug = FALSE, $type = 'form_params', $return_array_response = FALSE) {
    $auth = 'Bearer ' . $access_token;
    $location = '';
    $micropub_endpoint = Url::fromRoute('indieweb.micropub.endpoint', [], ['absolute' => TRUE])->toString();

    $client = \Drupal::httpClient();
    $headers = [
      'Accept' => 'application/json',
    ];

    // Access token is always in the headers when using Request from p3k.
    $headers['Authorization'] = $auth;

    try {
      $response = $client->post($micropub_endpoint, [$type => $post, 'headers' => $headers]);
      $status_code = $response->getStatusCode();
      $headersLocation = $response->getHeader('Location');
      if (!empty($headersLocation)) {
        $location = $headersLocation[0];
      }
    }
    catch (\Exception $e) {

      // Default 400 on exception.
      $status_code = 400;

      if (strpos($e->getMessage(), '401') !== FALSE) {
        $status_code = 401;
      }

      if (strpos($e->getMessage(), '403') !== FALSE) {
        $status_code = 403;
      }

      if ($debug) {
        debug($e->getMessage());
      }
    }

    if ($return_array_response) {
      return [
        'location' => $location,
        'code' => $status_code,
      ];
    }
    else {
      return $status_code;
    }
  }

  /**
   * Assert node count.
   *
   * @param $count
   * @param $type
   */
  protected function assertNodeCount($count, $type) {
    $node_count = \Drupal::database()->query('SELECT count(nid) FROM {node} WHERE type = :type', [':type' => $type])->fetchField();
    self::assertEquals($count, $node_count);
  }

  /**
   * Get the last nid.
   *
   * @param $type
   *
   * @return mixed
   */
  protected function getLastNid($type = '') {
    if ($type) {
      return \Drupal::database()->query('SELECT nid FROM {node} WHERE type = :type ORDER by nid DESC LIMIT 1', [':type' => $type])->fetchField();
    }
    else {
      return \Drupal::database()->query('SELECT nid FROM {node} ORDER by nid DESC LIMIT 1')->fetchField();
    }
  }

  /**
   * Assert queue items.
   *
   * @param array $urls
   * @param $id
   */
  protected function assertQueueItems($urls = [], $id = NULL) {
    if ($urls) {
      $count = \Drupal::queue(WEBMENTION_QUEUE_NAME)->numberOfItems();
      $this->assertTrue($count == count($urls));

      // We use a query here, don't want to use a while loop. When there's
      // nothing in the queue yet, the table won't exist, so the query will
      // fail. When the first item is inserted, we'll be fine.
      try {
        $query = 'SELECT * FROM {queue} WHERE name = :name';
        $records = \Drupal::database()->query($query, [':name' => WEBMENTION_QUEUE_NAME]);
        foreach ($records as $record) {
          $data = unserialize($record->data);
          if (!empty($data['source']) && !empty($data['target']) && $id) {
            $this->assertTrue(in_array($data['target'], $urls));
            if ($data['entity_type_id'] == 'node') {
              $this->assertEquals($data['source'], Url::fromRoute('entity.node.canonical', ['node' => $id], ['absolute' => TRUE])->toString());
            }
            elseif ($data['entity_type_id'] == 'comment') {
              $this->assertEquals($data['source'], Url::fromRoute('entity.comment.canonical', ['comment' => $id], ['absolute' => TRUE])->toString());
            }
          }
        }
      }
      catch (\Exception $ignored) {
        //debug($ignored->getMessage());
      }
    }
    else {
      $count = \Drupal::queue(WEBMENTION_QUEUE_NAME)->numberOfItems();
      $this->assertFalse($count);
    }
  }

  /**
   * Truncate the queue.
   */
  protected function clearQueue() {
    \Drupal::database()->delete('queue')->condition('name', WEBMENTION_QUEUE_NAME)->execute();
    $this->assertQueueItems();
  }

  /**
   * Runs the queue. Both calls cron and drush.
   */
  protected function runWebmentionQueue() {
    module_load_include('inc', 'indieweb', 'indieweb.drush');
    drush_indieweb_send_webmentions();
    indieweb_cron();
  }

  /**
   * Asserts a syndication.
   *
   * @param $source_id
   * @param $url
   */
  protected function assertSyndication($source_id, $url) {
    $object = \Drupal::database()->query('SELECT * FROM {webmention_syndication} WHERE entity_id = :id', [':id' => $source_id])->fetchObject();
    if (isset($object->url)) {
      self::assertEquals($url, $object->url);
    }
    else {
      // explicit fail
      $this->assertTrue($object, 'no syndication found');
    }
  }

  /**
   * Asserts no syndication.
   *
   * @param $source_id
   */
  protected function assertNoSyndication($source_id) {
    $false = \Drupal::database()->query('SELECT * FROM {webmention_syndication} WHERE entity_id = :id', [':id' => $source_id])->fetchField();
    self::assertTrue($false === FALSE);
  }

  /**
   * Create a syndication record.
   *
   * @param $url
   * @param string $entity_type_id
   * @param int $entity_id
   *
   * @throws \Exception
   */
  protected function createSyndication($url, $entity_type_id = 'node', $entity_id = 1) {
    $values = [
      'entity_id' => $entity_id,
      'entity_type_id' => $entity_type_id,
      'url' => $url
    ];

    \Drupal::database()
      ->insert('webmention_syndication')
      ->fields($values)
      ->execute();
  }

}
