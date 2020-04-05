<?php

use MigrateScraper\Sites;
use MigrateScraper\Menu;

require __DIR__.'/../vendor/autoload.php';

$siteId = isset($argv[1]) ? $argv[1] : '';
$pageId = isset($argv[2]) ? $argv[2] : '';

$sites = new Sites(__DIR__.'/sites.yml', __DIR__.'/');
$sitesList = $sites->load();
if ($siteId) {
  $site = $sitesList[$siteId];
  $file = __DIR__ . '/sites/' . $siteId . '/rootmenu.xml';
  $xml = simplexml_load_file($file);
  $menuParser = new Menu($siteId, $site);
  $menuParser->run($xml);
}
else {
  foreach ($sites as $site) {
  }
}
