<?php 
include_once('Keyword.php');

/*
 * Change everything to uppercase where applicable
 */
class UpperCase extends Keyword {
	
	public function evaluate($input) {
		return strtoupper($input);
	}
	
	public function addArgument($arg) {
		$this->typenode()->_except("Keyword " . $this->keyword() . " does not accept an argument");
	}
	
	public function keyword() {
		return "upper-case";
	}
}
?>
