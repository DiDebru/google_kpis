<?php

/**
 * @file
 * Contains google_kpis.drush.inc.
 */

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
    'aliases' => ['gkmm'],
  ];

  return $commands;
}

/**
 * Callback function for google_kpis drush command.
 *
 * Fetch and store google statistic data.
 *
 * @see Drupal\google_kpis\GoogleAnalyticsFetchAndStore
 */
function drush_google_kpis_fetch_and_store() {
  /** @var \Drupal\google_kpis\GoogleKpisFetchAndStore $service */
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
  // Fetch and store ga data yesterday once a day at 0:00.
  $service->fetchAndStoreGoogleAnylticsData('-1 day', 'today', 'daily');
  // Fetch and store gsc data last 7 days.
  $service->fetchAndStoreGoogleSearchConsoleData('-7 day', 'today');
  drush_print('Your google data was stored successful.');
}
