<?php

namespace Drupal\indieweb\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Feed entities.
 */
class FeedDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.indieweb_feed.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addMessage($this->t('Deleted @label.', ['@label' => $this->entity->label(),]));

    $form_state->setRedirectUrl($this->getCancelUrl());

    \Drupal::database()
      ->delete('indieweb_feed_items')
      ->condition('feed', $this->entity->id())
      ->execute();


    \Drupal::service('router.builder')->rebuild();
  }

}
