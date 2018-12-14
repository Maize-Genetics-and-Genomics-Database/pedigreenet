<?php 
include_once('Conditional.php'); 

/*
 * Logical alternative to a conditional
 */
class Alt extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function __construct($typenode) {
		parent::__construct($typenode);
		$this->isBeta();
	}
	
	#OVERRIDE: don't want alt on the call stack
	public function evaluate($input) {
		return $this->conditionalAssemble();
	}
	
	public function conditionalAssemble() {
		if ($this->cache == null) {
			$typenode = $this->typenode();
			
			# Get the last conditional
			$alternative = $typenode->_callstack()->lastPop();
			if ($alternative == null) {
				$this->typenode()->_except($this->keyword() . " can't locate previous conditional for which to provide an alternative");
			}
			
			if ($alternative->boolEvaluate()) { # alternative completed successfully; don't display alternative
				return;
			}
			
			# Use alternative instead
			$this->bool = true;
			$output = "";
			foreach ($typenode->_children() as $child) {
				$output .= $child->assemble();
			}
			
			$this->cache = $output;
		}
		
		return $this->cache;
	}
	
	public function addArgument($arg) {
		$this->typenode()->_except("Keyword " . $this->keyword() . " does not accept an argument");
	}
	
	public function boolEvaluate() {
		$this->conditionalAssemble();
		return $this->bool;
	}
	
	public function keyword() {
		return "alt";
	}
}
?>
