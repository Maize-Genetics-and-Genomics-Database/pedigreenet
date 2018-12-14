<?php 
include_once('Conditional.php');

/*
 * if-variable-equals (variable-id, $value)
 * 
 * Executes body if variable-id given as the first argument equals the value provided as the second argument 
 */
class UnlessVariableEquals extends Conditional {
	private $cache = null;
	private $bool  = false;
	
	public function conditionalAssemble() {
		if ($this->cache == null) { # In case this is being called by evaluate, cache the result so it won't have to be recalculated
			$typenode = $this->typenode();	
			$output = "";
			
			$arguments = $this->arguments();
			if (count($arguments) != 2) { # requires exactly 2 arguments
				$typenode->_except("Keyword " . $this->keyword() . " requires exactly two arguments: the identifier of the variable to test against and the value for which to check");
			}
			
			$vid   = $arguments[0];
			$value = $arguments[1];
			
			$variable = $typenode->_scope()->_symbols()->get($vid); # Check arguments from symbol table
			if ($variable == null) { # variable not found
				$typenode->_except("Symbol table lookup failed on argument \"$vid\"");
			}
			
			if ($variable->value() != $value) { # Pass!
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
		return "unless-variable-equals";
	}
}
?>
