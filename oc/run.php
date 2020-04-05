<?php

use MigrateScraper\Sites;
use MigrateScraper\Content;
use MigrateScraper\File;
use MigrateScraper\Override;
use League\Csv\Reader;

require __DIR__.'/../vendor/autoload.php';

require __DIR__.'/sites/ocpl/OcplOverride.php';
require __DIR__.'/sites/ocgov/OcGovOverride.php';

$siteId = isset($argv[1]) ? $argv[1] : '';
$pageId = isset($argv[2]) ? $argv[2] : '';

$sites = new Sites(__DIR__.'/sites.yml', __DIR__.'/');
$sitesList = $sites->load();
if ($siteId) {
  $site = $sitesList[$siteId];

  $csv = Reader::createFromPath($site['file'], 'r');
  $csv->setHeaderOffset(0);

  $urls = $csv->getRecords();
  $scraper = new Content($siteId, $site);

  $list = [];
  if ($pageId) {
    foreach ($urls as $url) {
      if ($url['id'] == $pageId) {
        $list = [$url];
      }
    }
    $scraper->run($list);
  }
  else {
    foreach ($urls as $url) {
      $list[] = $url;
    }
    $scraper->run($list);
  }
}
else {
  foreach ($sites as $site) {
    $csv = Reader::createFromPath($site['file'], 'r');
    $csv->setHeaderOffset(0);

    $urls = $csv->getRecords();
    $scraper = new Content($siteId, $site);

    $scraper->run($urls);

  }
}
