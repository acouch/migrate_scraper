<?php

namespace MigrateScraper;

use Symfony\Component\Yaml\Yaml;
use Wa72\HtmlPageDom\HtmlPageCrawler;

/**
 * Manages content for scraper.
 */
class Content extends MigrateScraper {

  /**
   * Directory to put json files.
   *
   * @var string
   */
  protected $fileDir = 'content';

  /**
   * Array of types for transforms.
   *
   * @var array
   */
  protected $transformTypes = ['tag' , 'override'];

  /**
   * Required fields for input list.
   *
   * @var array
   */
  private $requiredFields = ['url', 'id', 'parent_id'];

  /**
   * News and press release articles to be scraped.
   * 
   * @var array
   */
  protected $news = [];

  /**
   * Fields for exported files.
   *
   * @var array
   */
  private $fields = [
    'id',
    'title',
    'body',
    'type',
    'date',
    'parent_id',
    'url',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(string $id, array $site) {
    parent::__construct($id, $site);
    if (isset($site['transform'])) {
      $this->transforms = Yaml::parse(file_get_contents($site['transform']));
    }
    if (isset($site['filter'])) {
      $this->filterPages = Yaml::parse(file_get_contents($site['filter']));
    }
    if (isset($site['news'])) {
      $this->newsPages = Yaml::parse(file_get_contents($site['news']));
    }
    $this->file = new File($id, $site);
  }

  /**
   * Run the full content scrape.
   *
   * @param array $urls
   *   Array of urls.
   */
  public function run(array $urls) {
    foreach ($urls as $item) {
      $this->validateItem($item);
      $id = $item['id'];
      if ($crawler = $this->initCrawler($item['url'], $this->site['url'])) {
        if (!$this->filter ($item, $crawler)) {
          $record = $this->process($item, $crawler);
          // TODO: fix rest of transforms.
          /*
          * if ($this->transforms) {
          * $record = $this->processTransform($record, $crawler);
          * }
          */
          if ($this->site['override']) {
            $record = $this->processOverride($record, $crawler);
          }
          $this->writeRecord($record, $item['id']);
        }
      }
    }
    if ($this->newsPages) {
      $this->processNewsPages($this->newsPages);
    }
    $this->file->process();
    $this->log();
  }

  /**
   * Saves record to disk.
   */
  private function writeRecord($record, $id) {
    $this->writeToJsonFile($record, $this->fileDir . '/' . $id . '.json');
  }

  /**
   * Filters out items and reroutes for processing.
   * 
   * @return bool
   *   TRUE if record should be filtered.
   */
  private function filter($record, $crawler) {
    if (strpos($record['url'], 'NewsID')) {
      $this->print("Removing news page " . $record['url'] . " for ". $record['id']);
      return true;
    }
    if (strpos($record['url'], '/about/infooc/news/press')) {
      $this->print("Removing press release list page " . $record['url'] . " for ". $record['id']);
      return true;
    }
    if ($this->filterPages) {
      foreach ($this->filterPages as $page) {
        if ($page['id'] == $record['id']) {
          $this->print("Skipping page " . $record['url'] . " for ". $record['id']);
          return true;
        }
      }
    }
    return false;
  }

  /**
   * Process items for override.php includes.
   */
  private function processOverride($item, $crawler) {
    $override = new $this->site['override']($item, $crawler);
    return $override->process();
  }

  /**
   * Process individual items.
   *
   * @param array $item
   *   Page item.
   * @param object $crawler
   *   Goutte crawler.
   */
  private function process(array $item, $crawler) {
    $id = $item['id'];
    $this->print("Processing " . $id);
    $i = [];
    $empty = 'EMPTY FIELD';

    foreach ($this->fields as $field) {
      switch ($field) {
        case 'title':
          $tag = $this->getTag($this->site['fields']['title'], $this->transforms, $id, $crawler, 'title');
          if ($tag) {
            $title = $crawler->filter($tag);
            $i['title'] = $title->text();
          }
          else {
            $i['title'] = $empty;
          }
          break;

        case 'type':
          $i['type'] = 'county_page';
          break;

        case 'id':
          $i['id'] = $id;
          break;

        case 'parent_id':
          $i['parent_id'] = $item['parent_id'];
          break;

        case 'url':
          $i['url'] = $item['url'];
          break;

        case 'body':
          $tag = $this->getTag($this->site['fields']['body'], $this->transforms, $id, $crawler);
          if ($tag) {
            $body = $crawler->filter($tag);
            $i['body'] = $this->processBody($body);
          }
          else {
            $i['body'] = $empty;
          }
          break;
      }
    }

    return $i;
  }

  /**
   * Gets the tag. Checks transform.yml then selectors defined in sites.yml.
   *
   * @return string
   *   The css selector to get the body from.
   */
  private function getTag($tags, $transforms, $id, $crawler, $field = 'body') {
    if ($transforms[$id]) {
      foreach ($transforms[$id] as $transform) {
        if ($transform['type'] == 'tag' && $transform['field'] == $field) {
          $tag = $transform['tag'];
          $body = $crawler->filter($tag);
          if ($body->count()) {
            return $tag;
          }
          else {
            $this->errors[$id][] = "Nothing found for " . $tag . " in " . $id . " for " . $field;
          }
        }
      }
    }

    foreach ($this->site['fields'][$field] as $tag) {
      $text = $crawler->filter($tag);
      // Pick first result if there are multiple.
      if ($text->count()) {
        return $tag;
      }
    }
    $this->errors[$id][] = "No tags found in $id for $field";
    return FALSE;
  }

  /**
   * Cleans up the body.
   */
  private function processBody($text) {
    $text = $this->processImages($text);
    $text = $this->processLinks($text);
    return $text->html();
  }

  /**
   * Process images.
   */
  private function processImages($crawler) {
    $crawler->filter('img')->each(function($element) {
      $link = $element->attr('src');
      $id = substr(preg_replace('/\\.[^.\\s]{3,4}$/', '', $link), 1);
      if ($this->file->add($id, $link)) {
        $element->replaceWith('[image-link-' . $id . ']');
      }
      else {
        $element->replaceWith('');
      }
    });
    $crawler->saveHTML();
    return $crawler;
  }

  /**
   * Process links.
   */
  private function processLinks($crawler) {
    $crawler->filter('a')->each(function($element) {;
      $link = $element->link()->getUri();
      $text = $element->text();
      if ($fileId = $this->processLink($link)) {
        $element->replaceWith('[file-link-' . $fileId . '-' . $text . ']');
      }
    });
    $crawler->saveHTML();
    return $crawler;
  }

  /**
   * Process a remote link if a file it needs to be added to file registry.
   */
  private function processLink($link) {
    if ($id = $this->evalFileToSave($link)) {
      $this->file->add($id, $link);
      return $id;
    }
    return FALSE;
  }

  /**
   * Evaluate saving file to the regiser. Currently only want filebank files.
   */
  private function evalFileToSave($url) {
    // We want to retrieve files even if they are in another ocvgoc domain since
    // they are all the same cms.
    if ($parsed = parse_url($url)) {
      $url = $parsed['path'] . '?' . $parsed['query'];
    }
    $parts = explode('/', $url);
    // TODO: convert to regex.
    if ($parts[1] == 'civicax' && $parts[2] == 'filebank' || $parts[2] == 'inc') {      
      if (($id = str_replace('BlobID=', '', $parsed['query'])) || 
      ($id = str_replace('blobID=', '', $parsed['query']))) {
        return $id;
      }
      else {
        $this->errors[] = "No result for candidate URL: " . $url;
      }
    }
    return NULL;
  }

  /**
   * Takes a transform and process it.
   */
  private function processTransform($item, $crawler) {
    foreach ($this->transforms as $id => $transforms) {
      if ($item['id'] == $id) {
        foreach ($transforms as $transform) {
          switch ($transform['type']) {
            case 'tag':
              $tag = $crawler->filter($transform['tag']);
              if ($tag->count()) {
                $item[$transform['field']] = $tag->text();
              }
              break;

            case 'override':
              $item[$transform['field']] = $transform['text'];
              break;

          }
        }
      }
    }
    return $item;
  }

  /**
   * Validates items.
   */
  private function validateItem($item) {
    foreach ($this->requiredFields as $r) {
      if (!isset($item[$r])) {
        throw new Exception("No $r found in " . var_dump($item));
      }
    }
  }

  /**
   * Gets HTML of the page and initiates the crawler.
   */
  private function initCrawler($url, $siteUrl) {
    $response = $this->client->request('GET', trim($url), ['http_errors' => false]);
    if ($response->getStatusCode() != 200) {
      $this->errors[$id][] = "Status " . $response->getStatusCode(). " returned for " . $url;
      return FALSE;
    }
    else {
      $body = $response->getBody();
      $html = $body->getContents();
      return new HtmlPageCrawler($html, $siteUrl);
    }
  }

  /**
   * Processes news and press release pages from news.yml file.
   */
  public function processNewsPages($pages) {
    $record = [];
    foreach ($pages as $item) {
      if ($crawler = $this->initCrawler($item['url'], $this->site['url'])) {
        $newsTag = ".content-width-default table tr table tr";
        $crawler->filter($newsTag)->each(function($row) use($item) {
          if (count($row->filter(".newsheader"))) {
            $title = $row->filter(".newsheader")->text();
            $link = $row->filter(".newsheader")->attr('href');
            $body = $row->filter(".newsbody")->text();
            if ($fileId = $this->processLink($link)) {
              $readMore = '<p>[file-link-' . $fileId . '-Read full press release]</p>';
            }
            else {
              $readMore = '<p><a href="' . $link . '">Read full press release</a></p>';
            }
            $body = str_replace(" read more", $readMore, $body);
            $record = [
              'title' => $title,
              'parent_id' => $item['id'],
              'article_type' => $item['type'],
              'body' => $body,
              'type' => 'county_article',
            ];
            $this->news[] = $record;
          }
   
        });
      }
      else {
        $this->errors[$item['id']] = "Could not scrape news article: " . $item['id'];
      };
    }
    foreach ($this->news as $i => $page) {
      $id = $page['parent_id'] . '-' . $i;
      $this->print("Processing " .  $id);
      $page['id'] = $id;
      unset($page['parent_id']);
      $this->writeRecord($page, $id);
    }
  }
}
