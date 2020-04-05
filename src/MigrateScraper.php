<?php

namespace MigrateScraper;

use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

/**
 * Main class for MigrateScraper. Adds sites and client.
 */
class MigrateScraper {

  /**
   * Colors for CLI.
   *
   * @var array
   */
  protected $colors = [
    'green' => '0;32m',
    'light_green' => '1;32m',
    'red' => '0;31m',
  ];

  /**
   * Collects errors for logging.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Site settings.
   *
   * @var array
   */
  protected $site;

  /**
   * HTTP client for the site.
   *
   * @var array
   */
  protected $client;

  /**
   * Required fields for sites.
   *
   * @var array
   */
  private $requiredSite = ['url', 'file', 'fields', 'outputDir'];

  /**
   * {@inheritdoc}
   */
  public function __construct(string $id, array $site, bool $print = TRUE) {
    $this->client = new Client(['base_uri' => $site['url']]);
    $this->validateSite($site);
    $this->site = $site;
    $this->id = $id;
    $this->printOn = $print;
  }

  /**
   * Validates site.
   */
  private function validateSite($item) {
    foreach ($this->requiredSite as $r) {
      if (!isset($item[$r])) {
        throw new Exception("No $r found in " . var_dump($item));
      }
    }
  }

  /**
   * Creates uuid.
   */
  public function createUuid() {
    $uuidV4 = Uuid::uuid4();
    $uuid = $uuidV4->toString();
    return $uuid;
  }

  /**
   * Write items to a file.
   */
  public function writeToJsonFile($items, $name) {
    $fileName = $this->site['outputDir'] . '/' . $name;
    $json_data = json_encode($items, JSON_PRETTY_PRINT);
    file_put_contents($fileName, $json_data);
  }

  /**
   * Print errors collected.
   */
  public function log() {
    if ($this->errors) {
      var_dump($this->errors);
      $this->errors = [];
    }
  }

  /**
   * Prints to the command line.
   */
  public function print(string $string) {
    if ($this->printOn) {
      echo "\e[" . $this->colors['green'] . $string . "\e[0m \n";
    }
  }
}
