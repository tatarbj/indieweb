<?php

namespace Drupal\indieweb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to login with your domain..
 *
 * @Block(
 *   id = "indieweb_indieauth_login",
 *   admin_label = @Translation("Web sign-in"),
 * )
 */
class IndieAuthLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\indieweb\Form\IndieAuthLoginForm');
    return $build;
  }

}
