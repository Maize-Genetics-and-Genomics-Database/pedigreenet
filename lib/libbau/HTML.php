<?php
include_once('ImplicitType.php');

/*
 * Non-explicit type automatically created in a context-sensitive manner
 * 
 * @author: Bremen Braun
 */
class HTML extends ImplicitType {
	public function _type() {
		return 'HTML';
	}
	
	public function _unset() {
		unset($this);
		return 0;
	}
}
?>
