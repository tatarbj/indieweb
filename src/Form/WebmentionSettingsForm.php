<?php

namespace Drupal\indieweb\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WebmentionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['indieweb.webmention'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'indieweb_webmention_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('indieweb.webmention');

    $form['#attached']['library'][] = 'indieweb/admin';

    $form['webmention'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Webmention'),
    ];

    $form['webmention']['webmention_enable'] = [
      '#title' => $this->t('Collect webmentions'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('webmention_enable'),
    ];

    $form['webmention']['webmention_detect_identical'] = [
      '#title' => $this->t('Detect identical webmentions'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('webmention_detect_identical'),
      '#description' => $this->t('On some occasions it might be possible multiple webmentions are send with the same source, target and property. Enable to detect duplicates and not store those.'),
    ];

    $form['webmention']['webmention_endpoint'] = [
      '#title' => $this->t('Webmention endpoint'),
      '#type' => 'textfield',
      '#default_value' => $config->get('webmention_endpoint'),
      '#description' => $this->t('If you use webmention.io, the endpoint will look like <strong>https://webmention.io/@domain/webmention</strong><br />If you already have an account on webmention.io, you can use that URL (the domain is your username and does not matter).<br />This link will be added on all non admin pages. Leave empty if you are going to add this manually to html.html.twig.<br /><div class="indieweb-highlight-code">&lt;link rel="webmention" href="https://webmention.io/@domain/webmention" /&gt;</div>', ['@domain' => \Drupal::request()->getHttpHost()]),
      '#states' => array(
        'visible' => array(
          ':input[name="webmention_enable"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['webmention']['webmention_secret'] = [
      '#title' => $this->t('Webmention secret'),
      '#type' => 'textfield',
      '#default_value' => $config->get('webmention_secret'),
      '#description' => $this->t('When webmention.io receives a webmention, it can notify your site to send a post request to <strong>@domain/webmention/notify</strong></br >You can configure the notification endpoint and secret on webmention.io once you have received the first webmention there.', ['@domain' => \Drupal::request()->getSchemeAndHttpHost()]),
      '#states' => array(
        'visible' => array(
          ':input[name="webmention_enable"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['pingback'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pingback'),
    ];

    $form['pingback']['pingback_enable'] = [
      '#title' => $this->t('Collect pingbacks'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('pingback_enable'),
    ];

    $form['pingback']['pingback_endpoint'] = [
      '#title' => $this->t('Pingback endpoint'),
      '#type' => 'textfield',
      '#default_value' => $config->get('pingback_endpoint'),
      '#description' => $this->t('You do not need an account on webmention.io for this to work.<br />If you use webmention.io, the endpoint will look like <strong>https://webmention.io/webmention?forward=@domain/webmention/notify</strong><br />This link will be added on all non admin pages. Leave empty if you are going to add this manually to html.html.twig.<br /><div class="indieweb-highlight-code">&lt;link rel="pingback" href="https://webmention.io/webmention?forward=@domain/webmention/notify" /&gt;</div>', ['@domain' => \Drupal::request()->getSchemeAndHttpHost()]),
      '#states' => array(
        'visible' => array(
          ':input[name="pingback_enable"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['other'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Other'),
    ];

    $form['other']['webmention_uid'] = [
      '#type' => 'number',
      '#title' => $this->t('The user id which will own the collected webmentions'),
      '#default_value' => $config->get('webmention_uid'),
    ];

    $form['other']['webmention_log_payload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log the payload in watchdog on the notification endpoint.'),
      '#default_value' => $config->get('webmention_log_payload'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('indieweb.webmention')
      ->set('webmention_uid', $form_state->getValue('webmention_uid'))
      ->set('webmention_log_payload', $form_state->getValue('webmention_log_payload'))
      ->set('webmention_enable', $form_state->getValue('webmention_enable'))
      ->set('webmention_detect_identical', $form_state->getValue('webmention_detect_identical'))
      ->set('webmention_endpoint', $form_state->getValue('webmention_endpoint'))
      ->set('webmention_secret', $form_state->getValue('webmention_secret'))
      ->set('pingback_enable', $form_state->getValue('pingback_enable'))
      ->set('pingback_endpoint', $form_state->getValue('pingback_endpoint'))
      ->save();

    Cache::invalidateTags(['rendered']);

    parent::submitForm($form, $form_state);
  }

}
