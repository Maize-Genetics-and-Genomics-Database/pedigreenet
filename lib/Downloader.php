<?php

/*
 * A class for forcing download to file
 *
 * Author: Bremen Braun
 */
class Downloader {
  private $tmpdir;
  private $sendHeader;
  private $cleanup;

  function __construct($tmpdir='/tmp', $cleanup=false) {
    $this->tmpdir = $tmpdir;
    $this->sendHeader = true;
    $this->cleanup = $cleanup;
  }

  function sendHeader($bool=true) {
    $this->sendHeader = $bool;
  }

  function cleanup($bool=true) {
    $this->cleanup = $bool;
  }

  function download($file, $filename=false) {
    $ret = null;
    if (file_exists($file)) {
      $ret = $this->downloadFile($file, $filename);
    }
    else {
      $ret = $this->downloadString($file, $filename);
    }

    if ($this->cleanup) {
      if (file_exists($file)) {
        unlink($file);
      }
      if (file_exists($filename)) {
        unlink($filename);
      }
    }

    return $ret;
  }

  function downloadString($string, $filename="", $tmpdir=false) {
    $dest = $tmpdir ? $tmpdir : $this->tmpdir;
    $filename = $filename ? $filename : $this->generateFilename();

    $file = $dest . DIRECTORY_SEPARATOR . $filename;
    $status = file_put_contents($file, $string);
    if ($status !== false) {
      $this->downloadFile($file);
      unlink($file);
    }
    return $status;
  }

  function downloadFile($file, $filename="") {
    $file_extension = strtolower(substr(strrchr($file,"."), 1));
    switch ($file_extension) {
      case "pdf":
        $ctype = "application/pdf";
        break;
      case "exe":
        $ctype = "application/octet-stream";
        break;
      case "zip":
        $ctype = "application/zip";
        break;
      case "doc":
        $ctype = "application/msword";
        break;
      case "xls":
        $ctype = "application/vnd.ms-excel";
        break;
      case "ppt":
        $ctype="application/vnd.ms-powerpoint";
        break;
      case "gif":
        $ctype="image/gif";
        break;
      case "png":
        $ctype="image/png";
        break;
      case "jpeg":
      case "jpg":
        $ctype="image/jpg";
        break;
      default:
        $ctype="application/force-download";
    }

    if ($this->sendHeader) {
      header("Pragma: public");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      header("Cache-Control: private", false); // required for certain browsers
      header("Content-Type: $ctype; charset=UTF-8");
      header("Content-Disposition: attachment; filename=\"".basename($file)."\";" );
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: " . filesize($file));
    }
    return readfile($file);
  }

  private function generateFilename() {
    return uniqid('phpdownloader', true);
  }
}
?>
