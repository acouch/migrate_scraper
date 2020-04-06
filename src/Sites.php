<?php

/**
 * @file
 * Small class to manage sites.
 */

namespace MigrateScraper;
use Symfony\Component\Yaml\Yaml;

class Sites {

  protected $dirFields = ['file', 'outputDir', 'transform', 'filter', 'news'];

  function __construct(string $file, string $directory) {
    $this->file = $file;
    $this->dir = $directory;
  }

  private function addFullDir($sites) {
    foreach ($sites as $id => $site) {
      foreach ($this->dirFields as $field) {
        if (isset($site[$field])) {
          $sites[$id][$field] = $this->dir . $site[$field];
        }
      }
    }

    return $sites;
  }

  public function load() {
    $sites = Yaml::parse(file_get_contents($this->file));
    return $this->addFullDir($sites);
  }
}
