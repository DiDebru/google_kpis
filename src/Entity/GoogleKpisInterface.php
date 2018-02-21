<?php

namespace Drupal\google_kpis\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Google kpis entities.
 *
 * @ingroup google_kpis
 */
interface GoogleKpisInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Google kpis name.
   *
   * @return string
   *   Name of the Google kpis.
   */
  public function getName();

  /**
   * Sets the Google kpis name.
   *
   * @param string $name
   *   The Google kpis name.
   *
   * @return \Drupal\google_kpis\Entity\GoogleKpisInterface
   *   The called Google kpis entity.
   */
  public function setName($name);

  /**
   * Gets the Google kpis creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Google kpis.
   */
  public function getCreatedTime();

  /**
   * Sets the Google kpis creation timestamp.
   *
   * @param int $timestamp
   *   The Google kpis creation timestamp.
   *
   * @return \Drupal\google_kpis\Entity\GoogleKpisInterface
   *   The called Google kpis entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Google kpis published status indicator.
   *
   * Unpublished Google kpis are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Google kpis is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Google kpis.
   *
   * @param bool $published
   *   TRUE to set this Google kpis to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\google_kpis\Entity\GoogleKpisInterface
   *   The called Google kpis entity.
   */
  public function setPublished($published);

}
