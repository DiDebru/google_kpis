<?php

namespace Drupal\google_kpis\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GoogleKpisGlobalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_kpis_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_kpis.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('your_module.settings');
    $form['your_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Your message'),
      '#default_value' => $config->get('your_message'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('your_module.settings')
      ->set('your_message', $values['your_message'])
      ->save();
  }
}