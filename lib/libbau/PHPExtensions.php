<?php
/*
* Provide extensions for the interpreter for use in Code blocks
*
* Invoke with $this->function($args) inside Code
* 
* @author: Bremen Braun
*/
class PHPExtensions {
	private $instantiator;
	
	public function __construct($instantiator) {
		$this->instantiator = $instantiator;
	}
	
	/*
	* Create a looping variable to increment by the given value each
	* iteration
	*/
	function loop($variable, $initial_value=0, $increment=1) {
		$parent = $this->instantiator->reflect()->_parent()->_parent();
		$index  = $parent->get($variable); # attempt to locate Section $variable
		if ($index == null) {
			$invisisec = $parent->_addChild(new Section($variable));
			$index = $invisisec->_addChild(new Variable($variable, $initial_value));
			$invisisec->mute();
		}
		else {
			$index = $index->get($variable); # get the Variable from the eponymous Section
		}

		$ivalue = $index->value();
		$index->replace($ivalue + 1);
		
		return $ivalue;
	}

	/*
	* For looping sections, alternate between elements of array $array
	* using an index variable named $variable. Note that using a variable
	* that already exists will overwrite its value
	*/
	function alternate($variable, $array) {
		return $array[$this->loop($variable) % count($array)];
	}
	
	/*
	 * Import everything in the symbol table as a PHP variable 
	 */
	function import($variable_ids=array()) {
		$symbolTable = $this->instantiator->reflect()->_scope()->_symbols();
		
		$objects = array();
		/*
		if (count($variable_ids) == 0) { # export all
			foreach($symbolTable->items() as $resource) {
				$objects[$resource->value()->identifier()] = $resource->value();
			}
		}
		else { # find specific symbols*/
			foreach ($variable_ids as $variable_id) {
				$objects[$variable_id] = $symbolTable->get($variable_id);
			}
		#}
		
		return $objects;
	}
}
?>
