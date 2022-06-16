<?php

namespace Drupal\sd_layout_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Provides a block with a image.
 *
 * @Block(
 *   id = "SD_image_block",
 *   admin_label = @Translation("Image Block"),
 * )
 */
class SdImageBlock extends SdLayoutBlock {
  public $theme = 'sd_image_block';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $config = $this->getConfiguration();


    $image = null;
    $image_media_id = isset($config['image']) ? $config['image'] : NULL;
    $media = Media::load($image_media_id);
    $view_mode = isset($config['view_mode']) ? $config['view_mode'] : 'default';

    if ($media) {
      $image = \Drupal::entityTypeManager()->getViewBuilder('media')->view($media, $view_mode);
    }

    $build['#image'] = $image;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $image_id = isset($config['image']) ? intval($config['image']) : null;

    $form['block_content']['image'] = Array(
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => t('Image'),
      '#default_value' => $image_id,
    );

    $view_modes = \Drupal::service('entity_display.repository')
      ->getViewModeOptionsByBundle('media', 'image');
    // kint($view_modes);
    $view_mode_options = Array();
    foreach($view_modes as $key => $mode) {
      $view_mode_options[$key] = strval($mode);
    }


    $view_mode = isset($config['view_mode']) ? $config['view_mode'] : 'default';
    $form['block_content']['view_mode'] = Array(
      '#type' => 'select',
      '#title' => 'View Mode',
      '#options' => $view_mode_options,
      '#default_value' => $view_mode,
      '#description' => 'Specify the image\'s view mode'
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['image'] = $values['block_content']['image'];
    $this->configuration['view_mode'] = $values['block_content']['view_mode'];
  }
}
