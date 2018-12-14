<?php 
include_once('Keyword.php');

/*
 * Change everything to lowercase where applicable
 */
class LowerCase extends Keyword {
	
	public function evaluate($input) {
		return strtolower($input);
	}
	
	public function addArgument($arg) {
		$this->typenode()->_except("Keyword " . $this->keyword() . " does not accept an argument");
	}
	
	public function keyword() {
		return "lower-case";
	}
}
?>
