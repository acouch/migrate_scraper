<?php

namespace MigrateScraper;

/**
 * Manages files for scraping.
 */
class File extends MigrateScraper {

  /**
   * Array of files to process.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Name of exported file.
   *
   * @var string
   */
  public $fileName = 'file/file.json';

  /**
   * Directory to put files.
   *
   * @var string
   */
  protected $fileDir = 'files';

  /**
   * Saves file to internal register for processing.
   */
  public function add($id, $url, $type = 'file') {
    $url = str_replace($this->site['url'], '', $url);
    $response = $this->client->request('GET', trim($url), ['http_errors' => false]);
    if ($response->getStatusCode() != 200) {
      $this->errors[$id][] = "Status " . $response->getStatusCode(). " returned for " . $url . " from " . $id;
    }
    else {
      if ($headers = $response->getHeaders()) {
        $contentDisposition = $headers['content-disposition'];
        $type = $headers['Content-Type'][0];
        $length = $headers['Content-Length'][0];
        $uuid = $this->createUuid();
        $extension = $this->extensionFromType($type);
        $filename = $id . '.' . $extension;
        $name = $type == 'file' ? str_replace('inline;filename="', '', $contentDisposition[0]) : $filename;
        if (!$extension) {
          $this->errors[$id][] = "Could not find file extension for " . $id . " with content-type: " . $type . " from " . $url;
          $filename = $id;
        }
        $this->files[$id] = $this->format($name, $filename, $type, $length, $id, $uuid, $url, $response);
        return TRUE;
      }
      else {
        $this->errors[$id][] = "Problem accessing " . $url;
        return FALSE;
      }
    }
  }

  /**
   * Finds extension from common list of mime types.
   */
  private function extensionFromType($type) {
    $types = [
      'application/postscript' => 'ai',
      'image/bmp' => 'bmp',
      'application/vnd.ms-cab-compressed' => 'cab',
      'text/css' => 'css',
      'application/msword' => 'doc',
      'application/msword' => 'docx',
      'application/postscript' => 'eps',
      'application/x-msdownload' => 'exe',
      'video/x-flv' => 'flv',
      'image/gif' => 'gif',
      'text/html' => 'html',
      'image/vnd.microsoft.icon' => 'ico',
      'image/jpeg' => 'jpg',
      'image/jpg' => 'jpg',
      'application/javascript' => 'js',
      'application/json' => 'json',
      'video/quicktime' => 'mov',
      'audio/mpeg' => 'mp3',
      'application/x-msdownload' => 'msi',
      'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
      'application/vnd.oasis.opendocument.text' => 'odt',
      'application/pdf' => 'pdf',
      'text/html' => 'php',
      'image/png' => 'png',
      'image/x-png' => 'png',
      'application/vnd.ms-powerpoint' => 'ppt',
      'application/vnd.ms-powerpoint' => 'pptx',
      'application/postscript' => 'ps',
      'image/vnd.adobe.photoshop' => 'psd',
      'video/quicktime' => 'qt',
      'application/x-rar-compressed' => 'rar',
      'application/rtf' => 'rtf',
      'image/svg+xml' => 'svg',
      'image/svg+xml' => 'svgz',
      'application/x-shockwave-flash' => 'swf',
      'image/tiff' => 'tiff',
      'text/plain' => 'txt',
      'application/vnd.ms-excel' => 'xls',
      'application/vnd.ms-excel' => 'xlsx',
      'application/xml' => 'xml',
      'application/zip' => 'zip',
      'application/x-zip-compressed' => 'zip',      
    ];
    return isset($types[$type]) ? $types[$type] : false;
  }

  /**
   * Formats the file for output for migrate_staging.
   *
   * @param string $name
   *   Human readable file name.
   * @param string $filename
   *   Name of the file on disc.
   * @param string $type
   *   Mimetype for the file.
   * @param int $length
   *   Size of the file.
   * @param string $id
   *   Unique file id.
   * @param string $uuid
   *   Unique identifier.
   * @param string $url
   *   Original URL of file.
   */
  private function format($name, $filename, $type, $length, $id, $uuid, $url, $response) {

    return [
      'url' => $url,
      'response' => $response,
      'download' => $filename,
      'fid' => [
        ['value' => $id],
      ],
      'uuid' => [
        ['value' => $uuid],
      ],
      'langcode' => [
        ['value' => 'en'],
      ],
      'uid' => [
        ['target_id' => '1'],
      ],
      'filename' => [
        ['value' => $name],
      ],
      'uri' => [
        // TODO: move files to "migrated" folder.
        [
          'value' => 'public://' . $filename,
          'uri' => 'sites/default/files/' . $filename,
        ],
      ],
      'filemime' => [
        ['value' => $type],
      ],
      'filesize' => [
        ['value' => $length],
      ],
      'status' => [
        ['value' => TRUE],
      ],
      'created' => [
        [
          'value' => '2020-05-29T00:00:00+00:00',
          'format' => 'Y-m-d\\TH:i:sP',
        ],
      ],
    ];
  }

  /**
   * Create directory if it doesn't exist from the filename.
   */
  private function createDir($filename) {
    $dir = explode("/", $filename);
    array_pop($dir);
    $dir = implode("/", $dir);
    $dirpath = $this->site['outputDir'] . '/' . $this->fileDir . '/' . $dir;
    if (!is_dir($dirpath)) {
      mkdir($dirpath, 0777, true);
    }
  }

  /**
   * Download files and write to file.json and clear file register.
   */
  public function process() {
    foreach ($this->files as $i => $file) {
      $response = $file['response'];
      if ($fileContents = $response->getBody()->getContents()) {
        $this->print("Downloading file " . $file['download']);
        $this->createDir($file['download']);
        $filename = $this->site['outputDir'] . '/' . $this->fileDir . '/' . $file['download'];
        file_put_contents($filename, $fileContents);
      }
      else {
        $this->errors[] = "No result for candidate URL: " . $file['url'];
      }
      unset($this->files[$i]['url']);
      unset($this->files[$i]['response']);
      unset($this->files[$i]['download']);
    }
    foreach ($this->files as $file) {
      $files[] = $file;
    }
    // Saves the json record of the files.
    $output['file'] = $files;
    $this->writeToJsonFile($output, $this->fileName);
    // Remove files after processing.
    $this->files = [];
    $this->log();
  }

}
