<?php

namespace Drupal\indieweb\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MicrosubSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['indieweb.microsub'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'indieweb_microsub_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'indieweb/admin';

    $config = $this->config('indieweb.microsub');

    $form['microsub'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Microsub'),
      '#title_display' => 'hidden'
    ];

    $form['microsub']['enable'] = [
      '#title' => $this->t('Expose endpoint'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable'),
    ];

    $form['microsub']['microsub_endpoint'] = [
      '#title' => $this->t('Microsub endpoint'),
      '#type' => 'textfield',
      '#default_value' => $config->get('microsub_endpoint'),
      '#description' => $this->t('This link will be added on the front page. You can also add it yourself to html.html.twig:<br /><div class="indieweb-highlight-code">&lt;link rel="microsub" href="https://example.com/example-endpoint" /&gt;</div>'),
      '#states' => array(
        'visible' => array(
          ':input[name="enable"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $form['aperture'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Aperture'),
      '#description' => $this->t('If you use <a href="https://aperture.p3k.io" target="_blank">Aperture</a> as your Microsub server, you can send a micropub post to one channel when a webmention is received by this site.<br />The canonical example is to label that channel name as "Notifications" so you can view incoming webmentions on readers like Monocle or Indigenous.<br />Following webmentions are send: likes, reposts, bookmarks, mentions and replies.</a>'),
    ];

    $form['aperture']['aperture_enable_micropub'] = [
      '#title' => $this->t('Send micropub request to Aperture'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('aperture_enable_micropub'),
    ];

    $form['aperture']['aperture_api_key'] = [
      '#title' => $this->t('Channel API key'),
      '#type' => 'textfield',
      '#default_value' => $config->get('aperture_api_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('indieweb.microsub')
      ->set('enable', $form_state->getValue('enable'))
      ->set('microsub_endpoint', $form_state->getValue('microsub_endpoint'))
      ->set('aperture_enable_micropub', $form_state->getValue('aperture_enable_micropub'))
      ->set('aperture_api_key', $form_state->getValue('aperture_api_key'))
      ->save();

    Cache::invalidateTags(['rendered']);

    parent::submitForm($form, $form_state);
  }

}
