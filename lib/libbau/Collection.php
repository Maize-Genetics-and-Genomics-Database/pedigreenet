<?php
/*
 * Interface for a class which provides access to a list of items
 * 
 * @author: Bremen Braun
 */
interface Collection {
	public function add($item);
	public function merge($collection);
	public function clear();
	public function items();
}
?>
