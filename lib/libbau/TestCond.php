<?php 
include_once('Conditional.php');

/*
 * General if-statement
 */
class TestCond extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function __construct($typenode) {
		parent::__construct($typenode);
		$this->isBeta();
	}
	
	public function conditionalAssemble() {
		if ($this->cache == null) {
			$output   = "";
			$typenode = $this->typenode();
		
			# Check if the next node is a keyword
			$testnodes = $typenode->_children();
			$testnode  = $testnodes[0]; 		
			if ($testnode->_type() != 'Keyword') {
				$typenode->_except($this->keyword() . " requires a conditional to test");
			}
			
			# Evaluate the node
			$testnode = $testnode->_keyword();
			if ($testnode->boolEvaluate()) { # Success
				$this->bool = true;
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
		return "test-cond";
	}
}
?>
