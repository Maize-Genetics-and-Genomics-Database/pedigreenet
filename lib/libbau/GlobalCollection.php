<?php
include_once('KeyedCollection.php');

class GlobalCollection extends KeyedCollection implements Serializable {
	private static $instance;
	
	public static function instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function serialize() {
		return serialize($this->items());
	}
	
	public function unserialize($serialized) {
		$instance = self::instance();
		
		foreach (unserialize($serialized) as $item) {
			$instance->add($item);
		}
	}
}
?>