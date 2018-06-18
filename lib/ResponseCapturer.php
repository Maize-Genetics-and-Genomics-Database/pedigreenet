<?php

/*
 * Capture echoed output in a variable
 *
 * Author: Bremen Braun
 */
class ResponseCapturer {

  function __construct() {}

  /*
   * Execute a function, muting all echoed output and returning it
   */
  function capture($fn) {
    ob_start();
    $fn();
    $var = ob_get_contents();
    ob_end_clean();
    return $var;
  }
}
?>
