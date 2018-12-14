<?php
include_once('KeyCollection.php');
include_once('Resource.php');

/*
* Global state info not confined to a specific template scope
* 
* @author: Bremen Braun
*/
class KeyedCollection implements KeyCollection {
	private $items;
	
	public function __construct() {
		$this->items = array();
	}
	
	public function get($key) {
		if (isset($this->items[$key])) {
			return $this->items[$key];
		}
		
		return null;
	}
	
	public function set($key, $value) {
		if ($this->get($key) != null) {
			$this->items[$key] = $value;
			return true;
		}
		
		return false;
	}
	
	public function add($item) {
		$this->items[$item->key()] = $item->value();
		return true;
	}
	
	public function remove($key) {
		if (isset($this->items[$key])) {
			$ret = $this->items[$key];
			unset($this->items[$key]);
			return $ret;
		}
		
		return null;
	}
	
	public function merge($collection) {
		foreach ($collection->items() as $item) {
			$this->add($item);
		}
		
		return $this;
	}
	
	public function clear() {
		$this->items = array();
	}
	
	public function items() {
		$items = array();
		if ($this->items != null) { # oh you stupid PHP!
			foreach ($this->items as $key => $val) {
				array_push($items, new Resource($key, $val));
			}
		}
		
		return $items;
	}
}
?>
