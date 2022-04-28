<?php

namespace Drupal\sd_layout_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Block base for SD Layout Blocks
 */
class SdLayoutBlock extends BlockBase {

  protected function getNodeBody(){
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $content = Array (
        '#type' => 'processed_text',
        '#text' => $node->get('body')->value,
        '#format' => $node->get('body')->format,
      );
      return $content;
    }
    return null;
  }

  protected function getSetting($setting) {
    $settings = \Drupal::config('sd_layout_blocks.settings');
    return $settings->get('sd_layout_blocks.'.$setting);
  }

  protected function configOrDefault($config, $key, $default = null){
    if (isset($config[$key])) {
      return $config[$key];
    } else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = Array(
      '#theme' => $this->theme
    );
    $config = $this->getConfiguration();

    if (!isset($build['#attributes'])) {
      $build['#attributes'] = array();
    }
    if (!isset($build['#attributes']['class'])) {
      $build['#attributes']['class'] = array();
    }

    $label = $config['label'];

    $id = strtolower(str_replace('_','-', $this->theme)) .'--' . strtolower(str_replace(' ','-', $label));
    if (!isset($build['#attributes']['id'])) {
      $build['#attributes']['id'] = $id;
    }

    foreach($config['theme_styles'] as $class => $value) {
      if ($value){
        $build['#attributes']['class'][] = $class;
      }
    }

    if (isset($config['align_title']) && $config['align_title']){
      $build['#attributes']['class'][] = $config['align_title'];
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
    $config = $this->getConfiguration();

    if (property_exists($this, 'hide_title') && $this->hide_title) {
      $form['sd_layout_hide_title'] = array(
        '#type' => 'hidden',
        '#value' => true,
        '#default_value' => true
      );
    }

    $form['block_title'] = array(
      '#type' => 'details',
      '#title' => t('Block Title'),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );

    $form['block_content'] = array(
      '#type' => 'details',
      '#title' => t('Block Content'),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    );

    $form['block_style'] = array(
      '#type' => 'details',
      '#title' => t('Block Style'),
    );

    $heading_level = $this->configOrDefault($config, 'heading_level', 'h2');
    $form['block_title']['heading_level'] = array(
      '#title' => 'Heading Level',
      '#description' => 'Choose the tag to use for the block\'s title.',
      '#type' => 'select',
      '#options' => array(
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4'
      ),
      '#default_value' => $heading_level
    );

    $align_title = $this->configOrDefault($config, 'align_title', 'align-title-left');
    $form['block_title']['align_title'] = array(
      '#title' => 'Heading Alignment',
      '#description' => 'Align the block\'s title.',
      '#type' => 'select',
      '#options' => array(
        'align-title-left' => 'Left',
        'align-title-center' => 'Center',
        'align-title-right' => 'Right'
      ),
      '#default_value' => $align_title
    );

    $theme_styles = array();
    $styles = trim($this->getSetting('theme_styles'));
    $styles = explode("\n", $styles);
    foreach($styles as $style) {
      $class = trim(explode('|', $style)[0]);
      $label = trim(explode('|', $style)[1]);
      if ($class && $label) {
        $theme_styles[$class] = $label;
      }
    }

    $theme_styles_default = $this->configOrDefault($config, 'theme_styles');
    $form['block_style']['theme_styles'] = array(
      '#title' => 'Theme styles',
      '#description' => 'Configure theme-wide styles <a href="/admin/config/sd_layout_blocks/settings" target="_blank">here</a>, and add them to this block by selecting them above.',
      '#type' => 'checkboxes',
      '#options' => $theme_styles,
      '#default_value' => $theme_styles_default
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    if (isset($values['block_style'])){
      if (isset($values['block_style']['theme_styles'])) {
        $this->configuration['theme_styles'] = $values['block_style']['theme_styles'];
      }

      if (isset($values['block_title']['heading_level'])) {
        $this->configuration['heading_level'] = $values['block_title']['heading_level'];
      } else {
        $this->configuration['heading_level'] = 'h2';
      }

      if (isset($values['block_title']['align_title'])) {
        $this->configuration['align_title'] = $values['block_title']['align_title'];
      }
    }
  }
}
