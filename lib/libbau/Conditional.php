<?php
include_once('Keyword.php');

/*
 * A keyword can be conditional
 */
abstract class Conditional extends Keyword {
	
	function evaluate($input) {
		$this->typenode()->_callstack()->push($this);
		$return = $this->conditionalAssemble();
		$popped = $this->typenode()->_callstack()->pop();
		
		return $return;
	}
	
	/*
	 * evaluate to true or false
	 */
	abstract function boolEvaluate();
	
	/*
	 * Assemble wraps this so that the last conditional can be gotten
	 */
	abstract function conditionalAssemble();
}
?>
