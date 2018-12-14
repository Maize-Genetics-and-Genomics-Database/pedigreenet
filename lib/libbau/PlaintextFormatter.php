<?php
include_once('OutputFormatter.php');

/*
* Format output as plaintext
* 
* @author: Bremen Braun
*/
class PlaintextFormatter extends OutputFormatter {
	function streamprint($message) {
		print $message;
	}
}
?>
