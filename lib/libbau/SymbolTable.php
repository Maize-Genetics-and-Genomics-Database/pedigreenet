<?php
include_once('ResourceManifest.php');

/*
* A resource manifest especially for storing symbols
* 
* @author: Bremen Braun
*/
class SymbolTable extends ResourceManifest {
	#OVERRIDE
	public function get($key) {
		if ($key != null) {
			$type = parent::get($key);
			if ($type != null) { # key is already in the symbol table, set that it is a reference before returning it
				$type->_reference();
			
				return $type;
			}
		}
		
		return null; # key is not in the symbol table
	}
}
?>
