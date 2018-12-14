<?php 
include_once('Conditional.php');

/*
 * Check children for null values and hide display if found
 */
class UnlessEmpty extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function conditionalAssemble() {
		if ($this->cache == null) { # In case this is being called by evaluate, cache the result so it won't have to be recalculated
			$typenode = $this->typenode();	
			$output = "";
			
			$arguments = $this->arguments();
			if (count($arguments) == 0) { # no arguments given, test all children for empty
				foreach ($typenode->_children() as $child) {
					if ($child->value() == "") {
						return;
					}
					else {
						$output .= $child->assemble();
					}
				}
			}
			else { # Was given arguments, check from scope
				$symbols = $typenode->_scope()->_symbols();
				foreach ($arguments as $argument) {
					$symbol = $symbols->get($argument);
					if ($symbol == null) {
						$typenode->_except("Symbol table lookup failed on argument \"$argument\"");
					}
					if ($symbol->value() == "") { # Fail test
						return;
					}
				}
				
				# Passed test
				$this->bool = true;
				foreach ($typenode->_children() as $child) {
					$output .= $child->assemble();
				}
			}
			
			$this->cache = $output;
		}
		
		return $this->cache;
	}
	
	public function boolEvaluate() {
		$this->conditionalAssemble();
		return $this->bool;
	}
	
	public function keyword() {
		return "unless-empty";
	}
}
?>