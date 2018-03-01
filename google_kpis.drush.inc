<?php

/**
 * @file
 * Contains google_kpis.drush.inc.
 */


use Drupal\google_kpis\Entity\GoogleKpis;
use Drupal\google_kpis\GoogleKpisFetchAndStore;
use Drupal\node\Entity\Node;

/**
 * Implements hook_drush_command().
 */
function google_kpis_drush_command() {
  $commands['google_kpis_fetch_and_store'] = [
    'description' => 'Fetch and store google statistics.',
    'drupal dependencies' => ['google_kpis'],
    'aliases' => ['gkfs'],
    'options' => [
      'gsc' => 'trigger command for search console api',
      'ga' => 'trigger command for analytics api',
    ],
    'examples' => [
      'drush gkfs' => 'Fetch and store google statistic',
      'drush gkfs --gsc' => 'fetch and stores search console data only',
      'drush gkfs --ga' => 'fetch and stores analytics data only',
    ],
  ];

  $commands['google_kpis_migrate_mylife'] = [
    'description' => 'Migrate google statistics from mylife.',
    'drupal dependencies' => ['google_kpis'],
    'aliases' => ['gkmm'],
  ];


  return $commands;
}

/**
 * Callback function for google_kpis drush command.
 *
 * Fetch and store google statistic data.
 * @see Drupal\google_kpis\GoogleAnalyticsFetchAndStore.
 */
function drush_google_kpis_fetch_and_store() {
  /** @var GoogleKpisFetchAndStore $service */
  $service = Drupal::service('google_kpis.fetch_and_store');
  $gsc = drush_get_option('gsc');
  $ga = drush_get_option('ga');
  if ($gsc) {
    $service->fetchAndStoreGoogleSearchConsoleData('-7 day', 'today');
    drush_print('The search console API data fetched and stored successful.');
    return;
  }
  if ($ga) {
    $service->fetchAndStoreGoogleAnylticsData('-1 day', 'today', 'daily');
    drush_print('The analytics API data fetched and stored successful.');
    return;
  }
  // Fetch and store ga data yesterday once a day at 0:00,
  $service->fetchAndStoreGoogleAnylticsData('-1 day', 'today', 'daily');
  // Fetch and store gsc data last 7 days.
  $service->fetchAndStoreGoogleSearchConsoleData('-7 day', 'today');
  drush_print('Your google data was stored successful.');
}

function drush_google_kpis_migrate_mylife() {
  $articles_with_google_data = Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->exists('field_ga_sessions_storage')
    ->execute();
  $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
  foreach (array_chunk($articles_with_google_data, 20) as $chunk) {
    array_map(function (Node $node) {
      $sessions_storage = $node->field_ga_sessions_storage->getValue();
      $page_views_storage = $node->field_ga_pageviews_storage->getValue();
      $users_storage = $node->field_ga_users_storage->getValue();
      $og_searches_storage = $node->field_ga_ogsearches_storage->getValue();
      $gkpiid = \Drupal::entityQuery('google_kpis')
        ->condition('referenced_entity', $node->id())
        ->execute();
      if ($gkpiid) {
        $gkid = reset($gkpiid);
        $google_kpi = GoogleKpis::load($gkid);
        $google_kpi->set('field_sessions_storage', $sessions_storage);
        $google_kpi->set('field_page_views_storage', $page_views_storage);
        $google_kpi->set('field_users_storage', $users_storage);
        $google_kpi->set('field_og_searches_storage', $og_searches_storage);
        $google_kpi->save();
      }
      else {
        $google_kpi = GoogleKpis::create([
          'name' => $node->getTitle(),
          'referenced_entity' => $node->id(),
        ]);
        $google_kpi->set('field_sessions_storage', $sessions_storage);
        $google_kpi->set('field_page_views_storage', $page_views_storage);
        $google_kpi->set('field_users_storage', $users_storage);
        $google_kpi->set('field_og_searches_storage', $og_searches_storage);
        $google_kpi->save();
      }
    }, $nodeStorage->loadMultiple($chunk));
    $nodeStorage->resetCache();
  }
  drush_print('All storage fields were migrated successfully to google_kpis');
}