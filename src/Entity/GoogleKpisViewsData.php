<?php

namespace Drupal\google_kpis\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Google kpis entities.
 */
class GoogleKpisViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
