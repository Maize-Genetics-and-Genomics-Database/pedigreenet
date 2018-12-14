<?php
include_once('Interpreter.php');
include_once('PHPExtensions.php'); # helper functions for embedded code

/*
* The PHP interpreter. Provide support for PHP in Code sections
* 
* @author: Bremen Braun
*/
class PHP extends Interpreter {
	private $extensions;
	
	public function __construct($caller) {
		parent::__construct($caller);
		$this->extensions = new PHPExtensions($this);
	}
	
	#TODO: should this be moved to Code? Code directives can then export extensions
	public function extensions() {
		return $this->extensions;
	}
	
	public function interpret($code) {
		return eval($code);
	}
}
?>
