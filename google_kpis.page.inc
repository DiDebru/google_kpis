<?php

/**
 * @file
 * Contains google_kpis.page.inc.
 *
 * Page callback for Google kpis entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Google kpis templates.
 *
 * Default template: google_kpis.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_google_kpis(array &$variables) {
  // Fetch GoogleKpis Entity Object.
  $google_kpis = $variables['elements']['#google_kpis'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
