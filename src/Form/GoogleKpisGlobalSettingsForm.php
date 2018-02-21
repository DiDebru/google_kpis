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
    $config = $this->config('google_kpis.settings');
    $form['gsc_settings']['path_to_service_account_json'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The path to your google developer service account json auth.'),
      '#default_value' => $config->get('path_to_service_account_json'),
    );
    $form['gsc_settings']['start_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The date offset your query should start e.g. -7 days'),
      '#default_value' => $config->get('start_date'),
    );
    $form['gsc_settings']['end_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The date offset your query should end e.g. today'),
      '#default_value' => $config->get('end_date'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('google_kpis.settings')
      ->set('path_to_service_account_json', $values['path_to_service_account_json'])
      ->save();
  }
}