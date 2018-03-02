<?php

namespace Drupal\google_kpis\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class GoogleKpisController.
 */
class GoogleKpisController extends ControllerBase {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new GoogleKpisController object.
   */
  public function __construct(CurrentRouteMatch $current_route_match, EntityTypeManager $entity_type_manager, Connection $database) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * Show the kpis for the referenced node.
   *
   * @return array
   *   Return render array.
   */
  public function content() {
    $node = $this->currentRouteMatch->getParameter('node');
    if (is_numeric($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }
    if ($node instanceof Node) {
      $gkid = $this->entityTypeManager->getStorage('google_kpis')
        ->getQuery('AND')
        ->condition('referenced_entity', $node->id())
        ->execute();
      $gkid = reset($gkid);
      if ($node->hasField('field_google_kpis') && $gkid) {
        $node->set('field_google_kpis', $gkid);
        $node->save();
        $google_kpi = $this->entityTypeManager->getStorage('google_kpis')->load($gkid);
        $view_builder = $this->entityTypeManager->getViewBuilder('google_kpis');
        $view = $view_builder->view($google_kpi);
        return [
          '#type' => 'markup',
          '#markup' => render($view),
        ];
      }
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Nothing to show here , yet!'),
      ];
    }
  }

  /**
   * Checks access for a specific request.
   *
   * @return mixed
   *   Returns access if node has field_google_kpis.
   */
  public function access() {
    $node = Drupal::routeMatch()->getParameter('node');
    if ($node) {
      if (is_numeric($node)) {
        $node = Node::load($node);
      }
      return AccessResult::allowedIf($node->hasField('field_google_kpis'));
    }
  }

}
