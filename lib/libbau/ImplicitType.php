<?php
include_once('IType.php');
include_once('RootedTree.php');

/*
* Dumb class to allow cohesive treatment of a subtree
* 
* @author: Bremen Braun
*/
abstract class ImplicitType extends RootedTree implements IType {
	private $owner = null;
	
	public function identifier() {
		return null;
	}
	
	public function _owner($owner=null) {
		if ($owner != null) {
			$this->owner = $owner;
		}
		
		return $this->owner;
	}
	
	public function _onAdd($parent) {
		/* Intentionally blank */
	}
	
	public function _isReference() {
		return false;
	}
	
	public function _file() {
		return $this->_owner()->_file();
	}
	
	public function value() {
		return $this->_value();
	}
	
	public function assemble() {
		return $this->_value();
	}
}
