<?php 
include_once('Conditional.php');

/*
 * Logical opposite of if-equal
 */
class UnlessEqual extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function conditionalAssemble() {
		if ($this->cache == null) { # In case this is being called by evaluate, cache the result so it won't have to be recalculated
			$typenode = $this->typenode();	
			$output = "";
			
			$arguments = $this->arguments();
			if (count($arguments) < 2) { # need at least 2 arguments
				$typenode->_except("Keyword " . $this->keyword() . " requires at least two arguments");
			}
			
			$first_value = null;
			$all_equal   = true;
			$symbols  = $typenode->_scope()->_symbols(); # Check arguments from symbol table
			foreach ($arguments as $argument) {
				$symbol = $symbols->get($argument);
				if ($symbol == null) {
					$typenode->_except("Symbol table lookup failed on argument \"$argument\"");
				}
				
				if ($first_value == null) { # setting for comparison
					$first_value = $symbol->value();
				}
				else {
					if ($symbol->value() != $first_value) {
						$all_equal = false;
						break;
					}
				}
			}
			
			if (!$all_equal) { # Pass!
				$this->bool = true;
				foreach ($typenode->_children() as $child) {
					$output .= $child->assemble();
				}
			}
			
			# will be empty if fail
			$this->cache = $output;
		}
		
		return $this->cache;
	}
	
	public function boolEvaluate() {
		$this->conditionalAssemble();
		return $this->bool;
	}
	
	public function keyword() {
		return "unless-equal";
	}
}
?>