<?php
/*
* A collection made from key, value parts
* 
* @author: Bremen Braun
*/
interface KeyCollection extends Collection {
	function set($key, $val);
	function get($key);
	function remove($key);
}
?>
