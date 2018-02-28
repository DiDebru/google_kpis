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
    $form['gsc_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Search Console Settings'),
      '#open' => TRUE,
    ];
    $form['gsc_settings']['auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Search Console Authentification'),
    ];
    $form['gsc_settings']['query'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Search Console query settings'),
    ];
    $form['gsc_settings']['query']['gsc_start_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Start date'),
      '#description' => $this->t('The date offset your query should start e.g. -7 days'),
      '#size' => 10,
      '#default_value' => $config->get('gsc_start_date'),
      '#required' => TRUE,
    );
    $form['gsc_settings']['query']['gsc_end_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('End date'),
      '#description' => $this->t('The date offset your query should end e.g. today'),
      '#size' => 10,
      '#default_value' => $config->get('gsc_end_date'),
      '#required' => TRUE,
    );
    $form['gsc_settings']['query']['gsc_row_limit'] = array(
      '#type' => 'number',
      '#title' => $this->t('Row Limit'),
      '#description' => $this->t('The limit for your Google search console request default is 1000.'),
      '#step' => 100,
      '#default_value' => $config->get('gsc_row_limit'),
    );
    $form['gsc_settings']['query']['gsc_prod_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site Url'),
      '#description' => $this->t('The site you want to fetch data for.'),
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => $config->get('gsc_row_limit'),
    );
    $form['gsc_settings']['auth']['path_to_service_account_json'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The path to your google developer service account json auth.'),
      '#description' => $this->t('If your authentification file is outside of your webroot you will need a full ,from root to leaf, path! e.g. /home/user/path/to/my/auth.json'),
      '#size' => 60,
      '#default_value' => $config->get('path_to_service_account_json'),
    );
    $form['gsc_settings']['auth']['outside_webroot'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Google service account authentication file is outside of webroot'),
      '#default_value' => $config->get('outside_webroot'),
    );

    $form['ga_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Analytics Settings'),
      '#open' => TRUE,
    ];
    $form['ga_settings']['ga_start_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Start date'),
      '#description' => $this->t('The date offset your query should start e.g. -1 days'),
      '#size' => 10,
      '#default_value' => $config->get('ga_start_date'),
      '#required' => TRUE,
    );
    $form['ga_settings']['ga_end_date'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('End date'),
      '#description' => $this->t('The date offset your query should end e.g. today'),
      '#size' => 10,
      '#default_value' => $config->get('ga_end_date'),
      '#required' => TRUE,
    );
    $form['ga_settings']['max_storage'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Storage'),
      '#description' => $this->t('The maximum value you want your GA data to be stored, default is 29.'),
      '#default_value' => $config->get('ḿax_storage'),
      '#min' => 1,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('google_kpis.settings')
      ->set('outside_webroot', $values['outside_webroot'])
      ->set('path_to_service_account_json', $values['path_to_service_account_json'])
      ->set('gsc_start_date', $values['gsc_start_date'])
      ->set('gsc_end_date', $values['gsc_end_date'])
      ->set('gsc_row_limit', $values['gsc_row_limit'])
      ->set('gsc_prod_url', $values['gsc_prod_url'])
      ->set('ga_start_date', $values['ga_start_date'])
      ->set('ga_end_date', $values['ga_end_date'])
      ->set('max_storage', $values['max_storage'])
      ->save();
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if (!file_exists($values['path_to_service_account_json'])) {
      $form_state->setErrorByName('path_to_service_account_json', $this->t('The file you are trying to reference was not found, or Drupal cannot read it'));
    }
  }
}