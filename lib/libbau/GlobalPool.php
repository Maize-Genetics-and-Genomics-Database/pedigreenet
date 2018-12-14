<?php
include_once('Collection.php');
include_once('SingletonCollection.php');

/*
* A collection of objects which share global variables
* 
* @author: Bremen Braun
*/
class GlobalPool implements Collection {
	private $objects;
	private $globals;
	
	public function __construct() {
		$this->objects = array();
		$this->globals = new SingletonCollection();
	}
	
	public function add($item) {
		array_push($this->objects, $item);
	}
	
	public function merge($collection) {
		foreach ($collection as $item) {
			$this->add($item);
		}
		
		return $this;
	}
	
	public function clear() {
		$this->objects = array();
	}
	
	public function remove($object) {
		$index = 0;
		foreach ($this->objects as $stored) {
			if ($stored == $object) {
				$removed = $this->objects[$index];
				unset($this->objects[$index]);
				
				return $removed;
			}
			
			$index++;
		}
	}
	
	public function items() {
		return $this->objects;
	}
	
	public function globals() {
		return $this->globals;
	}
}
?>
