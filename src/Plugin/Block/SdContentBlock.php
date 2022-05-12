<?php

namespace Drupal\sd_layout_blocks\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;

/**
 * Provides a block with content content.
 *
 * @Block(
 *   id = "SD_content_block",
 *   admin_label = @Translation("Content Block"),
 * )
 */
class SdContentBlock extends SdLayoutBlock {
  public $theme = 'sd_content_block';
  public $hide_title = true;

  private function getViewModes($type) {
    $view_modes = \Drupal::service('entity_display.repository')
      ->getViewModeOptionsByBundle('node', $type);
    return $view_modes;
  }

  private function getContentTypes() {
    $types = \Drupal::entityTypeManager()
    ->getStorage('node_type')
    ->loadMultiple();

    $type_options = [];
    foreach($types as $key => $type) {
      $type_options[$key] = $type->get('name');
    }

    return $type_options;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $config = $this->getConfiguration();

    if(isset($config['content_node'])){
      $nid = $config['content_node'][0]['target_id'];
      $entity_type = 'node';
      $view_mode = isset($config['view_mode']) ? $config['view_mode'] : 'default';

      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $node = $storage->load($nid);
      $content = $view_builder->view($node, $view_mode);
      $build['#content'] = $content;
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


    $content_node = null;
    $content_type = null;
    if(isset($config['content_node'])) {
      $nid = $config['content_node'][0]['target_id'];
      if(isset($nid) && $nid){
        $node = Node::load($nid);
        if($node) {
          $content_node = $node;
          $content_type = $content_node->bundle();
        }
      }
    }

    $form['block_content']['content_node'] = array(
      '#type' => '',
      '#title' => 'Content Node',
      '#description' => 'Content to be shown in this block.',
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#tags' => TRUE,
      '#default_value' => $content_node,
      '#size' => 30,
      '#maxlength' => 1024,
      '#ajax' => array(
        'callback' => array($this, 'contentChangeCallback'),
        'disable-refocus' => FALSE, // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'edit-view-mode', // This element is updated with this AJAX callback.
        'progress' => array(
          'type' => 'throbber',
          'message' => $this->t('Updating view mode options...'),
        ),
      )
    );

    $this->contentFields($content_type, $form);

    return $form;
  }

  private function contentFields($content_type = null, &$form = null) {
    if (!$form) {
      $form = Array();
    }
    $type_options = $this->getContentTypes();
    $config = $this->getConfiguration();

    $view_modes = $this->getViewModes($content_type);
    $view_mode = isset($config['view_mode']) && isset($view_modes[$config['view_mode']]) ? $config['view_mode'] : 'default';
    $content_type_name = (isset($type_options[$content_type])) ? $type_options[$content_type].' ' : '';

    $form['block_content']['view_mode'] = Array(
      '#type' => 'select',
      '#title' => $content_type_name . 'Display Mode',
      '#options' => $view_modes,
      '#description' => 'Select the view mode to be displayed',
      '#default_value' => $view_mode,
      '#prefix' => '<div id="edit-view-mode">',
      '#suffix' => '</div>'
    );

    unset($form['block_title']);
    return $form['block_content'];
  }

  public function contentChangeCallback(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $value = $element['#value'];
    preg_match('/\(([0-9]+)\)/', $value, $matches);
    $nid = $matches[1];
    if ($nid){
      $node = Node::load($nid);
      $bundle = $node->bundle();
    }

    $elem = $this->contentFields($bundle, $elem);

    // If we want to execute AJAX commands our callback needs to return
    // an AjaxResponse object. let's create it and add our commands.
    $response = new AjaxResponse();

    // Issue a command that replaces the element #edit-output
    // with the rendered markup of the field created above.
    // ReplaceCommand() will take care of rendering our text field into HTML.
    $response->addCommand(new ReplaceCommand('#edit-view-mode', $elem));
    // Show the dialog box.
    // $response->addCommand(new OpenModalDialogCommand('My title', $dialogText, ['width' => '300']));

    // Finally return the AjaxResponse object.
    return $response;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    if (isset($values['block_content']['content_node'])) {
      $this->configuration['content_node'] = $values['block_content']['content_node'];
    }

    // check view mode against type
    if (isset($values['block_content']['view_mode'])){
      $this->configuration['view_mode'] = $values['block_content']['view_mode'];
    }
  }
}
