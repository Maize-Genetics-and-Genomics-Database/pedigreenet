<?php

class CallStack implements Serializable {
	private $stack;
	private $lastpop;
	private static $instance;
	
	public function __construct() {
		$this->stack   = array();
		$this->lastpop = null;
	}
	
	public static function instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function push($frame) {
		$instance = self::instance();
		array_push($instance->stack, $frame);
	}
	
	public function pop() {
		$instance = self::instance();
		$element  = array_pop($instance->stack);
		if ($element != null) {
			$this->lastpop = $element;
		}
		
		return $element;
	}
	
	function lastPop() {
		return $this->lastpop;
	}
	
	public function peek() {
		$instance = self::instance();
		$position = count($instance->stack) - 1;
		if ($position >= 0) {
			return $instance->stack[$position];
		}
		
		return null;
	}
	
	public function size() {
		$instance = self::instance();
		return count($instance->stack);
	}
	
	public function serialize() {
		return serialize($this->stack);
	}
	
	public function unserialize($serialized) {
		$instance = self::instance();
		
		foreach (unserialize($serialized) as $item) {
			$instance->push($item);
		}
	}
}
?>
