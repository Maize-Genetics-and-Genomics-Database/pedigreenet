<?php

/*
 * Provide extensions for Code sections
 * 
 * UNIMPLEMENTED
 * @author: Bremen Braun
 */
abstract class Extensions {
	private $instantiator;
	private $methodCache; # This will be an object with 
	
	public function __construct($instantiator) {
		$this->instantiator = $instantiator;
	}

	/*
	* Allow loading of user-defined extensions
	*/
	public function __call($name, $args) {
		$method = $this->methodCache->find($name);
		if ($method == null) { # couldn't find it
			die; #FIXME
		}
		
		#TODO
	}
}
?>
