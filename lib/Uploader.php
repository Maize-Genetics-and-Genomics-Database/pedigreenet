<?php

/*
 * Class for handling file uploads
 * For usage examples, see controllers/tools/breeders_toolbox.php
 *
 * Author: Bremen Braun
 */
class Uploader {
  private $files;
  private $directory;
  private $sizeLimit;
  private $typesLimit;
  private $overwrite;

  function __construct($directory=null) {
    $this->files = $_FILES;
    $this->sizeLimit = -1;
    $this->typesLimit = array();
    $this->overwrite = true;

    if ($directory == null) {
      $directory = $this->getSystemUploadDirectory();
    }
    $this->setUploadDirectory($directory);
  }

  function listFiles() {
    $files = array();
    foreach ($this->files as $name => $file) {
      array_push($files, $this->buildFile($file));
    }

    return $files;
  }

  function hasFiles() {
    return is_array($this->files) && count($this->files) > 0;
  }

  function getFirstFile() {
    $files = $this->listFiles();
    if (count($files) > 0) return $files[0];
    return null;
  }

  /*
   * Allow files to be overwritten by a newly moved upload
   */
  function overwrite($overwrite=true) {
    $this->overwrite = $overwrite;
  }

  /*
   * Set a maximum file size. If none is specified, the max size will be that
   * of upload_max_filesize in your php.ini
   */
  function limitToSize($size) {
    $this->sizeLimit = $size;
  }

  /*
   * Limit upload to a given mimetype. Note that this is easily spoofable so
   * this shouldn't be used for any real security
   */
  function limitToTypes($types) {
    $this->typesLimit = array();
  }

  /*
   * Set the directory to use as the temporary directory after the file has been
   * uploaded to the system temporary directory
   */
  function setUploadDirectory($dir) {
    if (!is_dir($dir)) {
      throw new Exception("Can't use '$dir' as a directory - not a directory");
    }
    $this->directory = $dir;
  }

  /*
   * Get the directory where files are uploaded. This value must be changed
   * through your php.ini file as it can't be configured in code
   */
  function getSystemUploadDirectory() {
    return sys_get_temp_dir();
  }

  /*
   * Get the directory where uploads are moved to from the system upload
   * directory. If these two values are the same, the file is not moved (duh)
   */
  function getUploadDirectory() {
    return $this->directory;
  }

  function uploadAs($name, $uploadName, $internalName=false) {
    return $this->upload($name, $internalName, $uploadName);
  }

  function uploadAsUnique($name, $internalName=false) {
    $uploadName = uniqid();
    return $this->upload($name, $internalName, $uploadName);
  }

  function upload($name, $internalName=false, $uploadName=false) {
    $file = null;
    if ($internalName) {
      if (empty($this->files[$name])) return false;
      $file = $this->buildFile($this->files[$name]);
    }
    else { // find by uploaded filename
      foreach ($this->listFiles() as $uploaded) {
        if ($uploaded->getName() == $name) {
          $file = $uploaded;
        }
      }
      if ($file == null) return false;
    }

    if ($file->getError() !== UPLOAD_ERR_OK) return false;
    if ($this->uploadPassesChecks($file)) {
      $path = $file->getTmpName();
      $uploadName = $uploadName ? $uploadName : $file->getName();
      if ($uploadName || ($this->getUploadDirectory() != $this->getSystemUploadDirectory())) {
        $path = $this->getUploadDirectory() . DIRECTORY_SEPARATOR . $uploadName;
        if (!move_uploaded_file($file->getTmpName(), $path)) return false;
      }

      return UploaderFile::builder()
        ->setName($uploadName)
        ->setType($file->getType())
        ->setSize($file->getSize())
        ->setError($file->getError())
        ->setTmpName($file->getTmpName())
        ->setPath($path)
        ->build();
    }
    return false;
  }

  private function buildFile($f) {
    return UploaderFile::builder()
      ->setName($f['name'])
      ->setType($f['type'])
      ->setSize($f['size'])
      ->setError($f['error'])
      ->setTmpName($f['tmp_name'])
      ->setPath($f['tmp_name'])
      ->build();
  }

  private function uploadPassesChecks($file) {
    if (!$this->overwrite) { // make sure moving this file won't result in an overwrite
      if ($this->getUploadDirectory() != $this->getSystemUploadDirectory()) {
        if (file_exists($this->getUploadDirectory() . DIRECTORY_SEPARATOR . $file->getName())) return false;
      }
    }
    if ($this->sizeLimit > 0 && $this->sizeLimit < $file->getSize()) return false;
    if (count($this->typesLimit) > 0 && !in_array($file->getType(), $this->typesLimit)) return false;
    return true;
  }
}

class UploaderFile {
  private $name;
  private $type;
  private $size;
  private $tmpName;
  private $path;
  private $error;

  function __construct($name, $type, $size, $tmpName, $path, $error="") {
    $this->name = $name;
    $this->type = $type;
    $this->size = $size;
    $this->tmpName = $tmpName;
    $this->path = $path;
    $this->error = $error;
  }

  static function builder() {
    return new UploaderFileBuilder();
  }

  function getName() {
    return $this->name;
  }

  function getType() {
    return $this->type;
  }

  function getSize() {
    return $this->size;
  }

  function getTmpName() {
    return $this->tmpName;
  }

  function getPath() {
    return $this->path;
  }

  function getContents() {
    return file_get_contents($this->getPath());
  }

  function getStream() {
    return new UploaderFileStream($this->getPath());
  }

  function getLines() {
    $lines = array();
    $stream = $this->getStream();
    while ($line = $stream->getLine()) {
      array_push($lines, $line);
    }
    return $lines;
  }

  function getError() {
    return $this->error;
  }
}

class UploaderFileStream {
  private $fh;

  function __construct($path) {
    $this->fh = fopen($path, 'r');
  }

  function getLine() {
    return fgets($this->fh);
  }

  function __destruct() {
    fclose($this->fh);
  }
}

class UploaderFileBuilder {
  private $name;
  private $type;
  private $size;
  private $tmpName;
  private $error;

  function __construct() {}

  function setName($name) {
    $this->name = $name;
    return $this;
  }

  function setType($type) {
    $this->type = $type;
    return $this;
  }

  function setSize($size) {
    $this->size = $size;
    return $this;
  }

  function setTmpName($name) {
    $this->tmpName = $name;
    return $this;
  }

  function setPath($path) {
    $this->path = $path;
    return $this;
  }

  function setError($error) {
    $this->error = $error;
    return $this;
  }

  function build() {
    return new UploaderFile($this->name, $this->type, $this->size, $this->tmpName, $this->path, $this->error);
  }
}
?>
