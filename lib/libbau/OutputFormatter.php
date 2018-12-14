<?php
/*
* Provide interchangeable output stream formatting
* 
* @author: Bremen Braun
*/
abstract class OutputFormatter {
	abstract function streamprint($message);
	
	#FIXME: also need stuff for markup formatting
}
?>
