<?php
include_once('Collection.php');

/*
 * @author: Bremen Braun
 */
class DirectiveManifest {
	private $items;
	
	public function __construct() {
		$this->items = array();
	}
	
	public function add($item) {
		array_push($this->items, $item);
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
		return $this->items;
	}
}
?>
