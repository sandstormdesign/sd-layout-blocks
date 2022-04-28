<?php

namespace Drupal\sd_layout_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a block with WYSIWYG content.
 *
 * @Block(
 *   id = "SD_wysiwyg_block",
 *   admin_label = @Translation("WYSIWYG Block"),
 * )
 */
class SdWYSIWYGBlock extends SdLayoutBlock {
  public $theme = 'sd_wysiwyg_block';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $config = $this->getConfiguration();

    $content = Array (
      '#type' => 'processed_text',
      '#text' => NULL,
      '#format' => 'basic_html',
    );

    if ($config['use_body']) {
      $body = $this->getNodeBody();
      $content = $body;
    } else if (isset($config['content']) && !empty($config['content'])) {
      if (isset($config['content']['value']) && !empty($config['content']['format'])) {
        $content['#text'] = trim($config['content']['value']);
      }
      if (isset($config['content']['format']) && !empty($config['content']['format'])) {
        $content['#format'] = $config['content']['format'];
      }
    }

    $build['#content'] = $content;

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
    $body = $this->getNodeBody();
    $use_body = isset($config['use_body']) ? $config['use_body'] : false;
    $form['block_content']['use_body'] = array(
      '#type' => 'checkbox',
      '#title' => 'Use node body',
      '#description' => 'If checked, this node\'s body field will be used for this block. If not, use the content field below.',
      '#default_value' => $use_body
    );

    $content = isset($config['content']) ? $config['content'] : null;
    $content_format = isset($content['format']) ? $content['format'] : 'basic_html';
    $content_value = isset($content['value']) ? $content['value'] : null;
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
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['content'] = $values['block_content']['content'];
    $this->configuration['use_body'] = $values['block_content']['use_body'];
  }
}
