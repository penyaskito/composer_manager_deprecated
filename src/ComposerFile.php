<?php

/**
 * @file
 * Contains \Drupal\composer_manager\ComposerManager.
 */

namespace Drupal\composer_manager;

use Drupal\Component\Utility\String;

class ComposerFile implements ComposerFileInterface {

  /**
   * @var string
   */
  protected $filepath;

  /**
   * @var array
   */
  protected $filedata;

  /**
   * @param string $filepath
   */
  public function __construct($filepath) {
    $this->filepath = $filepath;
  }

  /**
   * @return string
   */
  public function getFilepath() {
    return $this->filepath;
  }

  /**
   * Returns TRUE if the file exists and is a regular file.
   *
   * @return bool
   */
  public function exists() {
    return is_file($this->filepath);
  }

  /**
   * Returns TRUE if the file exists and is valid JSON.
   *
   * @return bool
   */
  public function isValidJson() {
    try {
      $this->read();
      return TRUE;
    } catch (\RuntimeException $e) {
      return FALSE;
    }
  }

  /**
   * Parses the contents of the Composer file into a PHP array.
   *
   * @return array
   *   The parsed json data.
   *
   * @throws \RuntimeException
   * @throws \UnexpectedValueException
   */
  public function read() {
    if (!isset($this->filedata)) {
      if (!$this->exists()) {
        throw new \RuntimeException(t('File does not exist: @filepath', array('@filepath' => $this->filepath)));
      }
      $json = file_get_contents($this->filepath);
      if ($json === FALSE) {
        throw new \RuntimeException(t('Could not read @filepath', array('@filepath' => $this->filepath)));
      }

      $filedata = json_decode($json, TRUE);
      if (JSON_ERROR_NONE !== json_last_error()) {
        throw new \UnexpectedValueException('Could not decode JSON: ' . $this->getLastErrorMessage());
      }

      if (!is_array($filedata)) {
        $this->filedata = array();
      }
      else {
        $this->filedata = $filedata;
      }
    }
    return $this->filedata;
  }

  /**
   * Converts the data to a JSON string and writes the file.
   *
   * @param array $filedata
   *   The Composer filedata to encode.
   *
   * @return int
   *   The number of bytes that were written to the file.
   *
   * @throws \RuntimeException
   * @throws \UnexpectedValueException
   */
  public function write(array $filedata) {
    if (file_exists($this->filepath) && !is_writable($this->filepath)) {
      throw new \RuntimeException(String::format('@filepath is not writable.', array('@filepath' => $this->filepath)));
    }

    $json = json_encode($filedata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (JSON_ERROR_NONE !== json_last_error()) {
      throw new \UnexpectedValueException('Could not encode JSON: ' . $this->getLastErrorMessage());
    }

    $bytes = file_put_contents($this->filepath, $json);
    if ($bytes === FALSE) {
      throw new \RuntimeException(String::format('Could not write to @filepath', array('@filepath' => $this->filepath)));
    }

    return $bytes;
  }

  /**
   * Returns a human readable json error.
   *
   * @return string
   *   The human readable json error.
   */
  protected function getLastErrorMessage()
  {
    if (function_exists('json_last_error_msg')) {
      // PHP 5.5 and later have a built-in function for this.
      return json_last_error_msg();
    }

    switch (json_last_error()) {
      case JSON_ERROR_DEPTH:
        return 'Maximum stack depth exceeded';
      case JSON_ERROR_STATE_MISMATCH:
        return 'Underflow or the modes mismatch';
      case JSON_ERROR_CTRL_CHAR:
        return 'Unexpected control character found';
      case JSON_ERROR_SYNTAX:
        return 'Syntax error, malformed JSON';
      case JSON_ERROR_UTF8:
        return 'Malformed UTF-8 characters, possibly incorrectly encoded';
      default:
        return 'Unknown error';
    }
  }

}
