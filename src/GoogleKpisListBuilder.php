<?php

namespace Drupal\google_kpis;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Google kpis entities.
 *
 * @ingroup google_kpis
 */
class GoogleKpisListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['referenced_entity'] = $this->t('Referenced Entity');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\google_kpis\Entity\GoogleKpis */
    $row['referenced_entity'] = $entity->getReferencedEntityId();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.google_kpis.edit_form',
      ['google_kpis' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
