<?php

/*
 * Interface for types
 * 
 * @author: Bremen Braun
 */
interface IType {
	public function _owner($owner=null);
	public function _onAdd($parent);
	public function _isReference();
	public function _type();
	public function _unset();
	public function _file();
}
?>
