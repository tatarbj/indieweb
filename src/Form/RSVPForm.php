<?php

namespace Drupal\indieweb\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\indieweb\Entity\Webmention;

class RSVPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'indieweb_rsvp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $rsvp = $this->getRSVP();
    $form['rsvp'] = [
      '#title' => $this->t('RSVP'),
      '#title_display' => 'hidden',
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => isset($rsvp->rsvp) ? $rsvp->rsvp : '',
      '#options' => [
        'yes' => 'I will attend',
        'no' => 'Can not make it',
        'maybe' => 'I might come',
        'interested' => 'I am interested',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update RSVP'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rsvp_value = $form_state->getValue('rsvp');
    $rsvp = $this->getRSVP();
    if (!empty($rsvp->id)) {
      \Drupal::database()->update('webmention_entity')
        ->fields(['rsvp' => $rsvp_value])
        ->condition('id', $rsvp->id)
        ->execute();
    }
    else {
      $values = [
        'user_id' => \Drupal::currentUser()->id(),
        'rsvp' => $rsvp_value,
        'property' => 'rsvp',
        'author_name' => \Drupal::currentUser()->getAccountName(),
        'target' => \Drupal::request()->getPathInfo(),
        'source' => \Drupal::request()->getSchemeAndHttpHost()
      ];
      $mention = Webmention::create($values);
      $mention->save();
    }

    drupal_set_message($this->t('Your RSVP has been updated.'));
  }

  /**
   * Gets the current RSVP status on this node for the current user.
   *
   * @return mixed
   */
  protected function getRSVP() {
    $args = [
      ':target' => \Drupal::request()->getPathInfo(),
      ':property' => 'rsvp',
      ':uid' => \Drupal::currentUser()->id(),
    ];
    return \Drupal::database()->query('SELECT id, rsvp FROM {webmention_entity} WHERE target = :target AND property = :property AND user_id = :uid', $args)->fetchObject();
  }

}
