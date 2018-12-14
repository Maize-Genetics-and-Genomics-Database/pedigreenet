<?php 
include_once('Conditional.php');

/*
 * Negate the result of a conditional
 */
class Not extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function conditionalAssemble() {
		if ($this->cache == null) {
			$output   = "";
			$typenode = $this->typenode();
		
			# Check if the next node is a keyword
			$testnodes = $typenode->_children();
			$testnode  = $testnodes[0]; 		
			if ($testnode->_type() != 'Keyword') {
				$typenode->_except($this->keyword() . " requires a conditional to negate");
			}
			
			# Evaluate the node
			$testnode = $testnode->_keyword();
			if (!$testnode->boolEvaluate()) { # Success
				foreach ($typenode->_children() as $child) {
					$output .= $child->assemble();
				}
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
		return "not";
	}
}
?>
