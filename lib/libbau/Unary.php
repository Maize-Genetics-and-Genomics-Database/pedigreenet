<?php
include_once('Type.php');
include_once('HTML.php');

/*
 * Base class for types which can hold no other explicit Bauplan types (Variable, Code)
 * 
 * @author: Bremen Braun
 */
abstract class Unary extends Type {
	private $child;
	
	public function __construct($value, $cvalue=null) {
		parent::__construct($value);
		$this->child = $this->_addChild(new HTML($cvalue));
	}
	
	public function assemble() {
		# Runtime checks
		if (count($this->_children()) > 1 || !$this->_getChild(0)->_isLeaf()) {
			$this->_except('Multiple children given for Unary type "' . $this->identifier() . '" (perhaps you forgot to declare $$SYNTAX-LEVEL?)');
		}
		
		return parent::assemble();
	}
	
	protected function child($value=null) {
		if ($value != null) {
			$this->child->_value($value);
		}
		
		return $this->child->_value();
	}
	
	public function _addChild($tree) {
		# Compile-time checks
		if (count($this->_children()) >= 1 || !$tree->_isLeaf()) {
			$this->_except('Attempt to add multiple children to Unary type "' . $this->identifier() . '" with "' . $tree->value() . '" (perhaps you forgot to declare $$SYNTAX-LEVEL?)');
		}
		
		return parent::_addChild($tree);
	}
	
	protected function cloneRecurse($clone) {
		$clone->child($this->child());
	}
	
}
?>
