<?php

/**
 * @file
 * OC Public Library overrides.
 */

use MigrateScraper\Override;

class OcplOverride extends Override {

  public function process() {
    $text = $this->crawler->text();
    if (strpos($this->item['title'], 'Contact') !== false) {
      $this->item['type'] = 'form';
    }
    else if (strpos($text, 'News Details') !== false) {
      $this->item['type'] = 'form';
    }
    return $this->item;
  }

}
