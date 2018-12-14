<?php
/*
* A class to implement preHTML, postHTML, etc.
* 
* @author: Bremen Braun
*/
class StringModifier {
	private $string;
	
	public function __construct($value="") {
		$this->string = $value;
	}
	
	public function clear() {
		$this->string = "";
	}
	
	public function append($value) {
		$this->string .= $value;
	}
	
	public function overwrite($value) {
		$this->string = $value;
	}
	
	public function value() {
		return $this->string;
	}
}
?>
