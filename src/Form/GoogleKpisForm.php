<?php

namespace Drupal\google_kpis\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Google kpis edit forms.
 *
 * @ingroup google_kpis
 */
class GoogleKpisForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\google_kpis\Entity\GoogleKpis */
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Google kpis.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Google kpis.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.google_kpis.canonical', ['google_kpis' => $entity->id()]);
  }

}
