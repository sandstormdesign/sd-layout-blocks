<?php

namespace Drupal\sd_layout_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Provides a block with a video.
 *
 * @Block(
 *   id = "SD_video_block",
 *   admin_label = @Translation("Video Block"),
 * )
 */
class SdVideoBlock extends SdLayoutBlock {
  public $theme = 'sd_video_block';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $config = $this->getConfiguration();


    $video = null;
    $video_media_id = isset($config['video']) ? $config['video'] : NULL;
    $media = Media::load($video_media_id);
    $view_mode = isset($config['view_mode']) ? $config['view_mode'] : 'default';

    if ($media) {
      $video = \Drupal::entityTypeManager()->getViewBuilder('media')->view($media, $view_mode);
    }

    $build['#video'] = $video;

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

    $video_id = isset($config['video']) ? intval($config['video']) : null;

    $form['block_content']['video'] = Array(
      '#type' => 'media_library',
      '#allowed_bundles' => ['remote_video'],
      '#title' => t('Video'),
      '#description' => 'Youtube or Vimeo video',
      '#default_value' => $video_id,
    );

    $view_modes = \Drupal::service('entity_display.repository')
      ->getViewModeOptionsByBundle('media', 'remote_video');
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
      '#description' => 'Specify the video\'s view mode'
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['video'] = $values['block_content']['video'];
    $this->configuration['view_mode'] = $values['block_content']['view_mode'];
  }
}
