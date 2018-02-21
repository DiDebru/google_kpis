<?php

namespace Drupal\google_kpis;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Google kpis entity.
 *
 * @see \Drupal\google_kpis\Entity\GoogleKpis.
 */
class GoogleKpisAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\google_kpis\Entity\GoogleKpisInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished google kpis entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published google kpis entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit google kpis entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete google kpis entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add google kpis entities');
  }

}
