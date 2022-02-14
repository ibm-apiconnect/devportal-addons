<?php

namespace Drupal\domain_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Domain block settings form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'domain_block_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['domain_block.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('domain_block.settings');

    $currentValue = $config->get('banned_domains');

    $form['intro'] = [
      '#markup' => t('Domain Block Settings'),
      '#weight' => -20,
    ];

    $form['domains'] = [
      '#type' => 'fieldset',
      '#title' => t('Domains'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['domains']['banned_domains'] = [
      '#type' => 'textarea',
      '#title' => t('Banned domains'),
      '#description' => t('List the banned domains separated by new lines.'),
      '#default_value' => $currentValue,
      '#required' => FALSE,
      '#wysiwyg' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Set the submitted configuration settings
    $banned_domains = $form_state->getValue('banned_domains');

    $this->config('domain_block.settings')
      ->set('banned_domains', $banned_domains)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
