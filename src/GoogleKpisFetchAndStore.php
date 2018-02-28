<?php

/**
 * @file Contains Class GoogleKpissFetchAndStore.
 */

namespace Drupal\google_kpis;

use Drupal;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Google_Client;
use Google_Exception;
use Google_Service_Webmasters;
use Google_Service_Webmasters_SearchAnalyticsQueryRequest;
use Drupal\google_kpis\Entity\GoogleKpis;


/**
 * Class GoogleAnalyticsFetchAndStore.
 */
class GoogleKpisFetchAndStore {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Google KPI Settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $googleKpisSettings;

  /**
   * The Google Analytics Reports Api Settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $googleAnalyticsReportsApiSettings;

  /**
   * Constructs a new GoogleAnalyticsFetchAndStore object.
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, ConfigFactory $config_factory) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->googleKpisSettings = $config_factory->get('google_kpis.settings');
    $this->googleAnalyticsReportsApiSettings = $config_factory->get('google_analytics_reports_api.settings');
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
   */
  public function fetchAndStoreGoogleAnylticsData() {
    /** @var \Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed $gaReports */
    $gaReports = google_analytics_reports_api_gafeed();
    $start_date = $this->googleKpisSettings->get('ga_start_date');
    $end_date = $this->googleKpisSettings->get('ga_end_date');
    $profile_id = $this->googleAnalyticsReportsApiSettings->get('profile_id');
    $params = [
      'profile_id' => 'ga:' . $profile_id,
      'start_date' => strtotime($start_date),
      'end_date' => strtotime($end_date),
      'metrics' => 'ga:pageviews, ga:users, ga:sessions, ga:organicsearches',
      'dimensions' => 'ga:pagePath',
      'sort_metric' => '-ga:pageviews',
    ];
    try {
      $ga_query = $gaReports->queryReportFeed($params);
      // Get all published articles.
      $published_nodes = $this->entityTypeManager->getStorage('node')
        ->getQuery('AND')
        ->condition('status', 1)
        ->execute();
      // Initialize variables.
      $ga_nodes = [];
      $count = NULL;
      $sessions_last_30 = NULL;
      $users_summary = NULL;
      $pageviews_summary = NULL;
      $ogsearches_summary = NULL;
      foreach ($ga_query->results->rows as $article) {
        // Get system path from path alias.
        $path_source = $this->database->select('url_alias', 'ua')->fields('ua', ['source'])->condition('alias', $article['pagePath'])->execute()->fetchField();
        // Get node id from system path.
        $node_id = filter_var($path_source, FILTER_SANITIZE_NUMBER_INT);
        if (is_numeric($node_id)) {
          if (!in_array($node_id, $ga_nodes)) {
            $ga_nodes[$node_id] = $node_id;
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
              /** @var GoogleKpis $google_kpi */
              $google_kpi = $this->linkGoogleKpiswithNode($node);
              // Get Storage value.
              $ga_sessions_storage = $google_kpi->field_sessions_storage->getValue();
              $ga_users_storage = $google_kpi->field_users_storage->getValue();
              $ga_pageviews_storage = $google_kpi->field_pageviews_storage->getValue();
              $ga_ogsearches_storage = $google_kpi->field_ogsearches_storage->getValue();
              // Add new value to array.
              $ga_sessions_storage[]['value'] = $ga_sessions;
              $ga_users_storage[]['value'] = $ga_users;
              $ga_pageviews_storage[]['value'] = $ga_pageviews;
              $ga_ogsearches_storage[]['value'] = $ga_organicsearches;
              // Summary of the last 30 elements.
              $max_storage = $this->googleKpisSettings->get('max_storage');
              if (!$max_storage || empty($max_storage) || $max_storage == 0) {
                $max_storage = 29;
              }
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
                $users_summary = $users_summary + $value['value'];
              }
              foreach ($ga_pageviews_storage as $delta => $value) {
                if ($delta > $max_storage) {
                  unset($ga_pageviews_storage[0]);
                  array_values($ga_pageviews_storage);
                }
                $pageviews_summary = $pageviews_summary + $value['value'];
              }
              foreach ($ga_ogsearches_storage as $delta => $value) {
                if ($delta > $max_storage) {
                  unset($ga_ogsearches_storage[0]);
                  array_values($ga_ogsearches_storage);
                }
                $ogsearches_summary = $ogsearches_summary + $value['value'];
              }
              // Set storage values.
              $google_kpi->set('field_sessions_storage', $ga_sessions_storage);
              $google_kpi->set('field_users_storage', $ga_users_storage);
              $google_kpi->set('field_page_views_storage', $ga_pageviews_storage);
              $google_kpi->set('field_og_searches_storage', $ga_ogsearches_storage);
              // Set values for 1 day.
              $google_kpi->set('field_sessions_yesterday', $ga_sessions);
              $google_kpi->set('field_users_yesterday', $ga_users);
              $google_kpi->set('field_page_views_yesterday', $ga_pageviews);
              $google_kpi->set('field_og_searches_yesterday', $ga_organicsearches);
              // Set storage summary for last 30 days.
              $google_kpi->set('field_sessions_summary', $sessions_last_30);
              $google_kpi->set('field_users_summary', $users_summary);
              $google_kpi->set('field_pageviews_summary', $pageviews_summary);
              $google_kpi->set('field_og_searches_summary', $ogsearches_summary);
              // Set summary variables back to NULL.
              $sessions_summary = NULL;
              $users_summary = NULL;
              $pageviews_summary = NULL;
              $ogsearches_summary = NULL;

              $node->setNewRevision(FALSE);
              $node->save();
              $count++;
            }
          }
        }
      }
      // Set fields value to 0 if the article is not in ga report.
      $count = NULL;
      // Get Articles that are not in GA report.
      $nodes_not_in_ga = array_diff($published_nodes, $ga_nodes);
      foreach ($nodes_not_in_ga as $revision_id => $nid) {
        if ($count && $count % 50 == '0') {
          // Reset static entity cache.
          $this->entityTypeManager->getStorage('google_kpis')->resetCache();
        }
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if ($node instanceof Node) {
          $google_kpi = $this->linkGoogleKpiswithNode($node);
          $google_kpi->set('field_sessions_yesterday', '0');
          $google_kpi->set('field_users_yesterday', '0');
          $google_kpi->set('field_page_views_yesterday', '0');
          $google_kpi->set('field_ogsearches_yesterday', '0');
          if (is_null($google_kpi->field_sessions_summary->value)) {
            $google_kpi->set('field_sessions_summary', '0');
          }
          if (is_null($google_kpi->field_users_summary->value)) {
            $google_kpi->set('field_users_summary', '0');
          }
          if (is_null($google_kpi->field_pageviews_summary->value)) {
            $google_kpi->set('field_pageviews_summary', '0');
          }
          if (is_null($google_kpi->field_ogsearches_summary->value)) {
            $google_kpi->set('field_og_searches_summary', '0');
          }
          $google_kpi->save();
          $count++;
        }
      }
    }
    catch(Google_Exception $exception) {
      Drupal::logger('google_kpis')->error($exception->getMessage());
    }


  }

  /**
   * Fetch and Store data from search console api.
   */
  public function fetchAndStoreGoogleSearchConsoleData() {
    $outside_webroot = $this->googleKpisSettings->get('outside_webroot');
    $path_to_auth_json = trim(strip_tags($this->googleKpisSettings->get('path_to_service_account_json')));
    $start_date = trim(strip_tags($this->googleKpisSettings->get('gsc_start_date')));
    $end_date = trim(strip_tags($this->googleKpisSettings->get('gsc_end_date')));
    $row_limit = $this->googleKpisSettings->get('gsc_row_limit');
    $site_url = $this->googleKpisSettings->get('gsc_prod_url');
    if (is_null($row_limit) || $row_limit == 0) {
      $row_limit = 1000;
    }
    if (file_exists($path_to_auth_json)) {
      if ($outside_webroot) {
        // Fetch GoogleSearchConsole data and store it.
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $path_to_auth_json);
      } else {
        // Fetch GoogleSearchConsole data and store it.
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . getcwd() . $path_to_auth_json);
      }
      $client = new Google_Client();
      $client->setApplicationName('mylife-backend-GA');
      $client->useApplicationDefaultCredentials();
      $client->setScopes([Google_Service_Webmasters::WEBMASTERS_READONLY]);
      $service = New Google_Service_Webmasters($client);
      $query = New Google_Service_Webmasters_SearchAnalyticsQueryRequest();
      $query->setStartDate(date("Y-m-d", strtotime($start_date)));
      $query->setEndDate(date('Y-m-d', strtotime($end_date)));
      $query->setDimensions(['page']);
      $query->setRowLimit($row_limit);
      try {
        $data = $service->searchanalytics->query('http://www.mylife.de', $query);
        $gsc_rows = $data->getRows();
        $gsc_nodes = [];
        $count = NULL;
        foreach ($gsc_rows as $gsc_row) {
          // Get uri from url.
          $alias = str_replace('http://www.mylife.de', '', $gsc_row->getKeys()[0]);
          // Get system path from path alias.
          $path_source = $this->database->select('url_alias', 'ua')->fields('ua', ['source'])->condition('alias', $alias)->execute()->fetchField();
          // Get node id from system path.
          $node_id = filter_var($path_source, FILTER_SANITIZE_NUMBER_INT);
          if (is_numeric($node_id)) {
            if (!in_array($node_id, $gsc_nodes)) {
              $gsc_nodes[$node_id] = $node_id;
              // Reset static entity cache all 50 iterations.
              if ($count && $count % 50 == '0') {
                $this->entityTypeManager->getStorage('node')->resetCache();
              }
              $node = $this->entityTypeManager->getStorage('node')->load($node_id);
              $google_kpi = $this->linkGoogleKpiswithNode($node);
              $gsc_ctr = $gsc_row->getCtr() * 100;
              $google_kpi->set('field_clicks', $gsc_row->getClicks());
              $google_kpi->set('field_impressions', $gsc_row->getImpressions());
              $google_kpi->set('field_ctr', $gsc_ctr);
              $google_kpi->set('field_position', $gsc_row->getPosition());
              $google_kpi->save();
              $count++;
            }
          }
        }
        $count = NULL;
        // Get all published Nodes.
        $published_nodes = $this->entityTypeManager->getStorage('node')->getQuery('AND')->condition('status', 1)->execute();
        // Get Nodes that are not in GSC report.
        $nodes_not_in_gsc = array_diff($published_nodes, $gsc_nodes);
        foreach ($nodes_not_in_gsc as $revision_id => $nid) {
          if ($count && $count % 50 == '0') {
            // Reset static entity cache.
            $this->entityTypeManager->getStorage('node')->resetCache();
          }
          if ($node instanceof Node) {
            $google_kpi = $this->linkGoogleKpiswithNode($node);
            $google_kpi->set('field_clicks', 0);
            $google_kpi->set('field_impressions', 0);
            $google_kpi->set('field_ctr', 0);
            $google_kpi->set('field_position', 99999);
            $google_kpi->save();
            $count++;
          }
        }
      } catch (Google_Exception $exception) {
        Drupal::logger('google_kpis')->error($exception->getMessage());
      }
    }
  }

  /**
   * Checks if the node has field_google_kpis, links to the google kpis entity,
   * if node has field_google_kpis.
   *
   * @param Node $node
   *
   * @return Drupal\Core\Entity\EntityInterface|GoogleKpis
   */
  public function linkGoogleKpiswithNode(Node $node) {
    $gkids = $this->entityTypeManager->getStorage('google_kpis')->getQuery('AND')->condition('referenced_entity', $node->id())->execute();
    $gkid = reset($gkids);
    if ($node instanceof Node && $node->hasField('field_google_kpis')) {
      $field_value = $node->field_google_kpis->entity;
      if ($field_value && $field_value instanceof GoogleKpis) {
        $google_kpi = $field_value;
      }
    } else if ($gkid) {
      $google_kpi = $this->entityTypeManager->getStorage('google_kpis')->load($gkid);
    } else {
      $google_kpi = GoogleKpis::create([
        'name' => $node->getTitle(),
        'referenced_entity' => $node->id(),
      ]);
      if ($node->hasField('field_google_kpis')) {
        $node->set('field_google_kpis', $google_kpi->id());
        $node->save();
      }
    }
    return $google_kpi;
  }
}
