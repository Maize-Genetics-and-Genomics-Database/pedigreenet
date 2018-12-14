<?php 
include_once('Stattable.php');

/*
 * Base class for debugging operations 
 */
abstract class StatOperation implements Stattable {
	private $owner;
	private $enabled;
	
	public function __construct($owner) {
		$this->owner   = $owner;
		$this->enabled = false;
	}
	
	function enable() {
		$this->enabled = true;
	}
	
	function disable() {
		$this->enabled = false;
	}
	
	function isEnabled() {
		return $this->enabled;
	}
	
	function print($message) {
		$analyzer = get_class($this);
		$owner    = $this->owner->identifier();
		
		return "Analyzer: $analyzer<br>\nNode: $owner<br>\n$message<br>\n"; # FIXME: should use output stream rather than calling directly
	}
}
?>