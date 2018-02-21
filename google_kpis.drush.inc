<?php

/**
 * @file
 * Contains google_kpis.drush.inc.
 */


use Drupal\google_kpis\GoogleKpisFetchAndStore;

/**
 * Implements hook_drush_command().
 */
function google_kpis_drush_command() {
  $commands['ga_fetch_and_store'] = [
    'description' => 'Fetch and store google statistics.',
    'drupal dependencies' => ['google_kpis'],
    'aliases' => ['gfs'],
    'options' => [
      'gsc' => 'trigger command for search console api',
      'ga' => 'trigger command for analytics api',
    ],
    'examples' => [
      'drush gfs' => 'Fetch and store google statistic',
      'drush gfs --gsc' => 'fetch and stores search console data only',
      'drush gfs --ga' => 'fetch and stores analytics data only',
    ],
  ];

  return $commands;
}

/**
 * Callback function for google_kpis drush command.
 *
 * Fetch and store google statistic data.
 * @see Drupal\google_kpis\GoogleAnalyticsFetchAndStore.
 */
function drush_google_kpis_ga_fetch_and_store() {
  $request_time = Drupal::time()->getRequestTime();
  // Key for daily cron.
  $key_daily = __FUNCTION__ . '_daily';
  $next_run_daily = Drupal::state()->get($key_daily, 0);
  /** @var GoogleAnalyticsFetchAndStore $service */
  $service = Drupal::service('google_kpis.fetchandstore');
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
  if ($next_run_daily < $request_time) {
    // Fetch and store ga data yesterday once a day at 0:00,
    $service->fetchAndStoreGoogleAnylticsData('-1 day', 'today', 'daily');
    // Fetch and store gsc data last 7 days.
    $service->fetchAndStoreGoogleSearchConsoleData('-7 day', 'today');
    // Run everyday at 0:00.
    Drupal::state()->set($key_daily, Cron\CronExpression::factory('0 0 * * *')->getNextRunDate()->format('U'));
  }
}