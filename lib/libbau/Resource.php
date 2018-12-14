<?php
/*
 * A key/val'd object for storage inside a registry
 * 
 * @author: Bremen Braun
 */
class Resource {
	private $key;
	private $value;
	
	public function __construct($key, $value) {
		$this->key   = $key;
		$this->value = $value;
	}
	
	public function key($key=null) {
		if ($key != null) {
			$this->key = $key;
		}
		
		return $this->key;
	}
	
	public function value($value=null) {
		if ($value != null) {
			$this->value = $value;
		}
		
		return $this->value;
	}
}
?>
