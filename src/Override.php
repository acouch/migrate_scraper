<?php

namespace MigrateScraper;

use Symfony\Component\DomCrawler\Crawler;

Abstract class Override {

  function __construct(array $item, Crawler $crawler) {
    $this->item = $item;
    $this->crawler = $crawler;
  }

  public function process() {
    if ($this->item['id'] == 'xyz') {
      // Process.
    }
    return $this->item;
  }
}
