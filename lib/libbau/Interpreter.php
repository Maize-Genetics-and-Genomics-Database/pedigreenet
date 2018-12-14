<?php
include_once('IInterpreter.php');

/*
 * Abstract class for defining interpreters
 * 
 * @author: Bremen Braun
 */
abstract class Interpreter implements IInterpreter {
	private $caller;
	
	public function __construct($caller) {
		$this->caller = $caller;
	}
	
	public function reflect() {
		return $this->caller;
	}
}
?>
