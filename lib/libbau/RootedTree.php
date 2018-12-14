<?php
include_once('Tree.php');

/*
 * Special tree type that provides access to its root in O(1) for every node
 * 
 * @author: Bremen Braun
 */
class RootedTree extends Tree {
	private $root; # pointer to parent's root
	
	public function __construct($value) {
		parent::__construct($value);
		$parent = $this->_parent();
		if ($parent != null) {
			$this->root = $this->_parent()->_root();
		}
		else {
			$this->root = $this;
		}
	}
	
	public function _root($root=null) {
		if ($root != null) {
			$this->root = $root; # FIXME: this shouldn't set the value but set the reference position
		}
		
		return $this->root;
	}
	
	# Overrides from Tree
	public function _addChild($tree) {
		$return = parent::_addChild($tree);
		$root = $this->root;
		if ($this->_parent() != null) {
			$root = $this->_parent()->_root();
		}
		$tree->_root($root);
		
		return $return;
	}
	
	public function _children($children=null) {
		$return = parent::_children($children);
		if ($children != null) {
			$root = $this->root;
			if ($this->_parent() != null) {
				$root = $this->_parent()->_root();
			}
			
			foreach ($this->_children() as $child) {
				$child->_root($root);
			}
		}
		return $return;
	}
}

?>
