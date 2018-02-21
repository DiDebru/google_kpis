<?php

/**
 * @file Contains Class GoogleKpissFetchAndStore.
 */

namespace Drupal\google_kpis;

use Drupal;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Google_Client;
use Google_Service_Webmasters;
use Google_Service_Webmasters_SearchAnalyticsQueryRequest;

/**
 * Class GoogleAnalyticsFetchAndStore.
 */
class GoogleKpisFetchAndStore {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GoogleAnalyticsFetchAndStore object.
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Fetch data from GA.
   * Get all published articles.
   * Flush static entity cache.
   * Store data to articles.
   *
   * @param $start_date
   *  The offset string the query should start e.g. "-1 day".
   * @param $end_date
   *  The offset string the query should end. e.g. "today".
   * @param $key
   *  Daily cron run not really necessary but keep this for further tasks.
   *
   */
  public function fetchAndStoreGoogleAnylticsData($start_date, $end_date, $key) {
    /** @var Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed $gaReports */
    $gaReports = google_analytics_reports_api_gafeed();
    $params = [
      'profile_id' => 'ga:130236505',
      'start_date' => strtotime($start_date),
      'end_date' => strtotime($end_date),
      'metrics' => 'ga:pageviews, ga:users, ga:sessions, ga:organicsearches',
      'dimensions' => 'ga:pagePath',
      'sort_metric' => '-ga:pageviews',
    ];
    $ga_query = $gaReports->queryReportFeed($params);
    // Get all published articles.
    $published_nodes = $this->entityTypeManager->getStorage('node')
      ->getQuery('AND')
      ->condition('status', 1)
      ->execute();
    // Initialize variables.
    $ga_articles = [];
    $count = NULL;
    $sessions_last_30 = NULL;
    $users_last_30 = NULL;
    $pageviews_last_30 = NULL;
    $ogsearches_last_30 = NULL;
    foreach ($ga_query->results->rows as $article) {
      // Get system path from path alias.
      $path_source = $this->database->select('url_alias', 'ua')->fields('ua', ['source'])->condition('alias', $article['pagePath'])->execute()->fetchField();
      // Get node id from system path.
      $node_id = filter_var($path_source, FILTER_SANITIZE_NUMBER_INT);
      if (is_numeric($node_id)) {
        if (!in_array($node_id, $ga_articles)) {
          $ga_articles[$node_id] = $node_id;
          // Reset static entity cache all 50 articles.
          if ($count && $count % 50 == '0') {
            // Reset static entity cache.
            $this->entityTypeManager->getStorage('node')->resetCache();
          }
          $node = $this->entityTypeManager->getStorage('node')->load($node_id);
          if ($node instanceof Node) {
            $ga_pageviews = $article['pageviews'];
            $ga_users = $article['users'];
            $ga_sessions = $article['sessions'];
            $ga_organicsearches = $article['organicsearches'];
            if ($key == 'daily') {
              // Get Storage value.
              $ga_sessions_storage = $node->field_ga_sessions_storage->getValue();
              $ga_users_storage = $node->field_ga_users_storage->getValue();
              $ga_pageviews_storage = $node->field_ga_pageviews_storage->getValue();
              $ga_ogsearches_storage = $node->field_ga_ogsearches_storage->getValue();
              // Add new value to array.
              $ga_sessions_storage[]['value'] = $ga_sessions;
              $ga_users_storage[]['value'] = $ga_users;
              $ga_pageviews_storage[]['value'] = $ga_pageviews;
              $ga_ogsearches_storage[]['value'] = $ga_organicsearches;
              // Summary of the last 30 elements.
              $max_storage = 29;
              foreach ($ga_sessions_storage as $delta => $value) {
                if ($delta > $max_storage) {
                  unset($ga_sessions_storage[0]);
                  array_values($ga_sessions_storage);
                }
                $sessions_last_30 = $sessions_last_30 + $value['value'];
              }
              foreach ($ga_users_storage as $delta => $value) {
                if ($delta > $max_storage) {
                  unset($ga_users_storage[0]);
                  array_values($ga_users_storage);
                }
                $users_last_30 = $users_last_30 + $value['value'];
              }
              foreach ($ga_pageviews_storage as $delta => $value) {
                if ($delta > $max_storage) {
                  unset($ga_pageviews_storage[0]);
                  array_values($ga_pageviews_storage);
                }
                $pageviews_last_30 = $pageviews_last_30 + $value['value'];
              }
              foreach ($ga_ogsearches_storage as $delta => $value) {
                if ($delta > $max_storage) {
                  unset($ga_ogsearches_storage[0]);
                  array_values($ga_ogsearches_storage);
                }
                $ogsearches_last_30 = $ogsearches_last_30 + $value['value'];
              }
              // Set storage values.
              $node->set('field_ga_sessions_storage', $ga_sessions_storage);
              $node->set('field_ga_users_storage', $ga_users_storage);
              $node->set('field_ga_pageviews_storage', $ga_pageviews_storage);
              $node->set('field_ga_ogsearches_storage', $ga_ogsearches_storage);
              // Set values for 1 day.
              $node->set('field_ga_sessions_yesterday', $ga_sessions);
              $node->set('field_ga_users_yesterday', $ga_users);
              $node->set('field_ga_pageviews_yesterday', $ga_pageviews);
              $node->set('field_ga_ogsearches_yesterday', $ga_organicsearches);
              // Set storage summary for last 30 days.
              $node->set('field_ga_sessions_last_30_days', $sessions_last_30);
              $node->set('field_ga_users_last_30_days', $users_last_30);
              $node->set('field_ga_pageviews_last_30_days', $pageviews_last_30);
              $node->set('field_ga_ogsearches_last_30_days', $ogsearches_last_30);
              // Set summary variables back to NULL.
              $sessions_last_30 = NULL;
              $users_last_30 = NULL;
              $pageviews_last_30 = NULL;
              $ogsearches_last_30 = NULL;
            }
            $node->setNewRevision(FALSE);
            $node->save();
            $count++;
          }
        }
      }
    }
    // Set fields value to 0 if the article is not in ga report.
    $count = NULL;
    if ($key == 'daily') {
      // Get Articles that are not in GA report.
      $articles_not_in_ga = array_diff($published_articles, $ga_articles);
      foreach ($articles_not_in_ga as $revision_id => $nid) {
        if ($count && $count % 50 == '0') {
          // Reset static entity cache.
          $this->entityTypeManager->getStorage('node')->resetCache();
        }
        $node = Node::load($nid);
        if ($node instanceof Node && $node->bundle() == 'article') {
          $node->set('field_ga_sessions_yesterday', '0');
          $node->set('field_ga_users_yesterday', '0');
          $node->set('field_ga_pageviews_yesterday', '0');
          $node->set('field_ga_ogsearches_yesterday', '0');
          if (is_null($node->field_ga_sessions_last_30_days->value)) {
            $node->set('field_ga_sessions_last_30_days', '0');
          }
          if (is_null($node->field_ga_users_last_30_days->value)) {
            $node->set('field_ga_users_last_30_days', '0');
          }
          if (is_null($node->field_ga_pageviews_last_30_days->value)) {
            $node->set('field_ga_pageviews_last_30_days', '0');
          }
          if (is_null($node->field_ga_ogsearches_last_30_days->value)) {
            $node->set('field_ga_ogsearches_last_30_days', '0');
          }
          $node->setNewRevision(FALSE);
          $node->save();
          $count++;
        }
      }
    }
  }

  /**
   * Fetch and Store data from search console api.
   *
   * @param $start_date
   *   The offset string the query should start e.g. "-1 day".
   * @param $end_date
   *   The offset string the query should end. e.g. "today".
   */
  public function fetchAndStoreGoogleSearchConsoleData($start_date, $end_date) {
    // Fetch GoogleSearchConsole data and store it.
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . getcwd() . '/sites/default/mylife-backend-GA-7dbd547ff698.json');
    $client = new Google_Client();
    $client->setApplicationName('mylife-backend-GA');
    $client->useApplicationDefaultCredentials();
    $client->setScopes([Google_Service_Webmasters::WEBMASTERS_READONLY]);
    $service = New Google_Service_Webmasters($client);
    $query = New Google_Service_Webmasters_SearchAnalyticsQueryRequest();
    $query->setStartDate(date("Y-m-d", strtotime($start_date)));
    $query->setEndDate(date('Y-m-d', strtotime($end_date)));
    $query->setDimensions(['page']);
    $query->setRowLimit(5000);
    $data = $service->searchanalytics->query('http://www.mylife.de', $query);
    $gsc_rows = $data->getRows();
    $gsc_articles = [];
    $count = NULL;
    foreach ($gsc_rows as $gsc_row) {
      // Get uri from url.
      $alias = str_replace('http://www.mylife.de', '', $gsc_row->getKeys()[0]);
      // Get system path from path alias.
      $path_source = $this->database->select('url_alias', 'ua')->fields('ua', ['source'])->condition('alias', $alias)->execute()->fetchField();
      // Get node id from system path.
      $node_id = filter_var($path_source, FILTER_SANITIZE_NUMBER_INT);
      if (is_numeric($node_id)) {
        if (!in_array($node_id, $gsc_articles)) {
          $gsc_articles[$node_id] = $node_id;
          // Reset static entity cache all 50 iterations.
          if ($count && $count % 50 == '0') {
            $this->entityTypeManager->getStorage('node')->resetCache();
          }
          $node = $this->entityTypeManager->getStorage('node')->load($node_id);
          if ($node instanceof Node && $node->bundle() == 'article') {
            $gsc_ctr = $gsc_row->getCtr() * 100;
            $node->set('field_gsc_clicks', $gsc_row->getClicks());
            $node->set('field_gsc_impressions', $gsc_row->getImpressions());
            $node->set('field_gsc_ctr', $gsc_ctr);
            $node->set('field_gsc_position', $gsc_row->getPosition());
            $node->setNewRevision(FALSE);
            $node->save();
            $count++;
          }
        }
      }
    }
    $count = NULL;
    // Get all published articles.
    $published_articles = $this->entityTypeManager->getStorage('node')
      ->getQuery('AND')
      ->condition('type', 'article')
      ->condition('status', 1)
      ->execute();
    // Get Articles that are not in GSC report.
    $articles_not_in_gsc = array_diff($published_articles, $gsc_articles);
    foreach ($articles_not_in_gsc as $revision_id => $nid) {
      if ($count && $count % 50 == '0') {
        // Reset static entity cache.
        $this->entityTypeManager->getStorage('node')->resetCache();
      }
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node instanceof Node && $node->bundle() == 'article') {
        $node->set('field_gsc_clicks', 0);
        $node->set('field_gsc_impressions', 0);
        $node->set('field_gsc_ctr', 0);
        $node->set('field_gsc_position', 99999);
        $node->setNewRevision(FALSE);
        $node->save();
        $count++;
      }
    }
  }

}
