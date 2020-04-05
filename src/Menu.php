<?php

namespace MigrateScraper;

/**
 * Manages menu for scraping.
 */
class Menu {

  /**
   * Name of exported file.
   *
   * @var string
   */
  public $fileName = 'menu/menu.json';

  /**
   * Map of UUIDs.
   *
   * @var array
   */
  private $uuidMap = [];

  /**
   * Runs the menu migration.
   */
  public function run($xml) {
    $menu = [];
    $this->createTopLevel($menu);
    $this->parse($xml, $menu);
    $output['menu_link_content'] = $menu;
    $this->writeToJsonFile($output, '/' . $this->$fileName);
    $this->log();
  }

  /**
   * Creates the top level items since they are defined differently in the XML.
   */
  private function createTopLevel(&$menu) {
    $weight = 0;
    foreach ($this->site['topMenu'] as $item) {
      $title = $item['title'];
      $id = $item['id'];
      $url = '';
      $uuid = $this->createUuid();
      $this->uuidMap[$id] = $uuid;
      $parent = $this->deriveParent($id, $uuid);
      $menu[] = $this->format($title, $url, $id, $weight, $uuid, $parent);
      $weight++;
    }
  }

  /**
   * Parses the XML file.
   */
  private function parse($xml, &$menu) {
    foreach ($xml->children() as $item) {
      $weight = 0;
      $hasChild = (count($item->children()) > 0) ? true: false;
      $attributes = $item->attributes();
      $id = (string) $attributes->id;
      $url = (string) $attributes->url;
      if ($id && $url) {
        $title = strval(trim($item->children()));
        // $weight = $this->deriveWeight($id);
        $uuid = $this->createUuid($id);
        $parent = $this->deriveParent($id, $uuid);
        $menu[] = $this->format($title, $url, $id, $weight, $uuid, $parent);
        $weight++;
      }
      if ($hasChild) {
        $this->parse($item, $menu);
      }
    }
  }

  /**
   * Formats menu output.
   */
  private function format($title, $url, $id, $weight, $uuid, $parent = []) {
    return [
      'title' => [
        ['value' => $title],
      ],
      'link' => [
        ['uri' => $this->site['url'] . $url],
      ],
      'enabled' => [
        ['value' => TRUE],
      ],
      'external' => [
        ['value' => TRUE],
      ],
      'menu_name' => [
        ['value' => 'main'],
      ],
      'weight' => [
        ['value' => $weight],
      ],
      'parent' => [
        ['value' => $parent],
      ],
      'weight' => [
        ['value' => $weight],
      ],
      'id' => [
        ['value' => $id],
      ],
      'uuid' => [
        ['value' => $uuid],
      ],
    ];
  }

  /**
   * Finds parent by the id from the XML.
   */
  private function deriveParent($id) {
    $ids = explode('_', $id);
    array_pop($ids);
    $parentId = implode('_', $ids);
    if (isset($this->uuidMap[$parentId])) {
      $parentUuid = $this->uuidMap[$parentId];
      return 'menu_link_content:' . $parentUuid;
    }
    array_pop($ids);
    $parentId = implode('_', $ids);
    if (isset($this->uuidMap[$parentId])) {
      $parentUuid = $this->uuidMap[$parentId];
      return 'menu_link_content:' . $parentUuid;
    }
    return [];
  }

}
