<?php
include_once('OutputFormatter.php');

/*
* Format streams for HTML presentation
* 
* @author: Bremen Braun
*/
class HTMLFormatter extends OutputFormatter {
	function streamprint($message) {
		echo $message;
	}
}
?>
