<?php 
include_once('Conditional.php');

/*
 * Logical opposite of unless-empty
 */
class IfEmpty extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function conditionalAssemble() {
		if ($this->cache == null) { # In case this is being called by evaluate, cache the result so it won't have to be recalculated
			$typenode = $this->typenode();	
			$output = "";
			
			$arguments = $this->arguments();
			if (count($arguments) == 0) { # arguments must be given
				$typenode->_except("Keyword " . $this->keyword() . " requires at least one argument");
			}
			
			$allEmpty = true;
			$symbols  = $typenode->_scope()->_symbols(); # Check arguments from symbol table
			foreach ($arguments as $argument) {
				$symbol  = $symbols->get($argument);
				if ($symbol == null) {
					$typenode->_except("Symbol table lookup failed on argument \"$argument\"");
				}
				
				if ($symbol->value() != "") { # Fail if not empty
					$allEmpty = false;
					break;
				}
			}
			
			if ($allEmpty) { # Pass!
				$this->bool = true;
				foreach ($typenode->_children() as $child) {
					$output .= $child->assemble();
				}
			}
			
			# Output will be nothing on failure
			$this->cache = $output;
		}	
		
		return $this->cache;
	}
	
	public function boolEvaluate() {
		$this->conditionalAssemble();
		return $this->bool;
	}
	
	public function keyword() {
		return "if-empty";
	}
}
?>