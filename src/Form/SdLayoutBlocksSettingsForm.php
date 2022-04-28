<?php

namespace Drupal\sd_layout_blocks\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SdLayoutBlocksSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sd_layout_blocks_settings_form';
  }

  // Return config value if set, otherwise default value.
  private function configORdefault($setting, $default = null) {
    $config = $this->config('sd_layout_blocks.settings');
    $config_val = $config->get('sd_layout_blocks.'.$setting);
    if (NULL !== $config_val){
      return $config_val;
    }
    return $default;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('sd_layout_blocks.settings');

    $form['theme_styles'] = array(
      '#title' => 'Theme Styles',
      '#type' => 'textarea',
      '#default_value' => $this->configORdefault('theme_styles'),
      '#description' => "<p>Classes available to add to any SD Layout Block. Add one per line in the following format:</p><pre>class-name-1 | Class Name 1\nclass-name-2 | Class Name 2</pre>"
    );

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sd_layout_blocks.settings');
    $config->set('sd_layout_blocks.theme_styles', $form_state->getValue('theme_styles'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }
   /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sd_layout_blocks.settings',
    ];
  }
}
