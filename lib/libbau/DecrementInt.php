<?php 
include_once('Keyword.php');

/*
 * Increment a variable's value if it can be converted to an int
 */
class DecrementInt extends Keyword {
	
	public function evaluate($input) {
		$typenode = $this->typenode();
		
		# Check for correct number of arguments
		$arguments = $this->arguments();
		if (count($arguments) < 1 ||  count($arguments) > 2) {
			$typenode->_except("Keyword " . $this->keyword() . " requires one or more argument(s)");
		}
		
		# Make sure argument can be converted to an int
		$identifier = $arguments[0];
		$decrement  = 1;
		if (count($arguments == 2)) {
			$decrement = $arguments[1];
		}
		$symbol = $typenode->_scope()->_symbols()->get($identifier); # Fetch variable from the symbol table
		if ($symbol == null) {
			$typenode->_except("Symbol table lookup failed on argument \"$argument\"");
		}
		if ($symbol->_type() != "Variable") {
			$typenode->_except("Keyword " . $this->keyword . " requires an argument which is an identifier for a variable in scope");
		}
		
		$int = $symbol->value() + 0; # coerce to int
		
		# Replace value and return it
		$newval = $int - $decrement;
		$symbol->replace($newval);
		return $newval;
	}
	
	public function keyword() {
		return "decrement-int";
	}
}
?>