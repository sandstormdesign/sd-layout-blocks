<?php

namespace Drupal\sd_layout_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;

/**
 * Provides a block with Large Teaser content.
 *
 * @Block(
 *   id = "sd_large_teaser_block",
 *   admin_label = @Translation("Large Teaser Block"),
 * )
 */
class SdLargeTeaserBlock extends SdLayoutBlock {
  public $theme = 'sd_large_teaser_block';
  public $hide_title = true;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $config = $this->getConfiguration();

    $build['#title'] = $config['label'];
    $build['#heading_level'] = isset($config['heading_level']) ? $config['heading_level'] : 'h2';


    $content = Array (
      '#type' => 'processed_text',
      '#text' => NULL,
      '#format' => 'basic_html',
    );

    if (isset($config['content']) && !empty($config['content'])) {
      if (isset($config['content']['value']) && !empty($config['content']['format'])) {
        $content['#text'] = trim($config['content']['value']);
      }
      if (isset($config['content']['format']) && !empty($config['content']['format'])) {
        $content['#format'] = $config['content']['format'];
      }
    }

    $build['#content'] = $content;

    $link_text = isset($config['link_text']) ? $config['link_text'] : '';
    $link_url = isset($config['link_url']) ? Url::fromUri($config['link_url']) : null;
    if ($link_text && $link_url){
      $link = Link::fromTextAndUrl(t($link_text), $link_url);
      $link = $link->toRenderable();
      if (isset($config['link_label'])){
        $link['#attributes']['class'] = Array('more-link');
        $link['#attributes']['aria-label'] = $config['link_label'];
        $build['#link'] = $link;
      }
    }
    $media = Media::load($config['image']);
    $view_mode = 'default';
    $build['#image'] = \Drupal::entityTypeManager()->getViewBuilder('media')->view($media, $view_mode);

    $bg = $config['overlay_color'];
    $build['#overlay_color'] = $bg;

    $fg = _sd_layout_blocks_get_contrast_color($bg);
    if (strtolower($fg) == '#fff') {
      $build['#attributes']['class'][] = 'text-invert';
    } else {
      $build['#attributes']['class'][] = 'text-default';
    }

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

    $content = $this->configOrDefault($config, 'content');
    $content_format = $this->configOrDefault($content, 'format', 'basic_html');
    $content_value = $this->configOrDefault($content, 'value');

    $form['block_content']['content'] = array(
      '#title' => 'Content',
      '#type' => 'text_format',
      '#format' => $content_format,
      '#default_value' => $content_value,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[block_content][use_body]"]' => array(
            'checked' => FALSE,
          ),
        ),
      ),
      '#required' => true
    );
    $form['block_content']['link'] = Array(
      '#type' => 'fieldset',
      '#title' => 'Link'
    );

    $link_text = $this->configOrDefault($config, 'link_text');
    $form['block_content']['link']['text'] = Array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $link_text,
      '#description' => 'Text displayed to the user.',
      '#required' => true
    );

    $link_label = $this->configOrDefault($config, 'link_label');
    $form['block_content']['link']['label'] = Array(
      '#type' => 'textfield',
      '#title' => $this->t('Link Label'),
      '#default_value' => $link_label,
      '#description' => 'Aria-label text provided to non-sighted users.'
    );

    $link_url = $this->configOrDefault($config, 'link_url');
    $form['block_content']['link']['url'] = Array(
      '#type' => 'url',
      '#title' => $this->t('Link URL'),
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => 1]),
      '#default_value' => $link_url,
      '#required' => true
    );

    $image = isset($config['image']) ? intval($config['image']) : NULL;
    $form['block_content']['image'] = Array(
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => t('Image'),
      '#description' => t('Upload or select an image.'),
      '#default_value' => $image,
      '#required' => true
    );

    $overlay_color = $this->configOrDefault($config, 'overlay_color');
    $form['block_content']['overlay_color'] = Array(
      '#title' => 'Overlay color',
      '#type' => 'color',
      '#default_value' => $overlay_color,
      '#description' => 'This color will provided the background color for content and fade into the image. Contrasting text color will be automatically calculated.',
      '#required' => true
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    if (isset($values['block_content'])) {
      if (isset($values['block_content']['content'])) {
        $this->configuration['content'] = $values['block_content']['content'];
      }

      if (isset($values['block_content']['link'])) {
        if (isset($values['block_content']['link']['text'])) {
          $this->configuration['link_text'] = $values['block_content']['link']['text'];
        }
        if (isset($values['block_content']['link']['label'])) {
          $this->configuration['link_label'] = $values['block_content']['link']['label'];
        }
        if (isset($values['block_content']['link']['url'])) {
          $this->configuration['link_url'] = $values['block_content']['link']['url'];
        }
      }

      if (isset($values['block_content']['image'])) {
        $this->configuration['image'] = $values['block_content']['image'];
      }

      if (isset($values['block_content']['overlay_color'])) {
        $this->configuration['overlay_color'] = $values['block_content']['overlay_color'];
      }
    }
  }
}
