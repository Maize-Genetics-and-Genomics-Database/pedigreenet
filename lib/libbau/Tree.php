<?php
/*
 * A basic tree structure with just enough methods to be useful
 * 
 * @author: Bremen Braun
 */
class Tree {
	private $value;
	private $parent;
	private $children;
	
	public function __construct($value) {
		$this->value    = $value;
		$this->parent   = null;
		$this->children = array();
	}
	
	public function _value($value=null) {
		if ($value != null) {
			$this->value = $value;
		}
		
		return $this->value;
	}
	
	public function _parent($parent=null) {
		if ($parent != null) {
			$this->parent = $parent;
		}
		
		return $this->parent;
	}
	
	public function _children($children=null) {
		if ($children != null) {
			$this->children = $children;
		}
		
		return $this->children;
	}
	
	public function _addChild($tree) {
		$tree->parent = $this;
		array_push($this->children, $tree);
		
		return $tree;
	}
	
	public function _addChildAtIndex($tree, $index) {
		$tree->parent = $this;
		
		if ($index == 0) { # beginning
			$this->children = array_push($tree, $this->children);
		}
		elseif ($index == count($this->children)) { # end
			array_push($this->children, $tree);
		}
		else { # middle
			$head   = array_slice($this->children, 0, $index);
			$tail   = array_slice($this->children, $index);
			$insert = array($tree);
			
			$this->children = array_merge($head, $insert, $tail);
		}
		
		return $tree;
	}
	
	public function _removeChildAtIndex($index) {
		if ($index < 0 || $index > count($this->children)) {
			return null;
		}
		
		$return = array_splice($this->children, $index, 1);
		return $return[0];
	}
	
	public function _getChild($index) {
		if ($index < 0 || $index > count($this->children)) {
			return null;
		}
		
		return $this->children[$index];
	}
	
	public function _isLeaf() {
		return count($this->children) == 0;
	}
	
	public function _isRoot() {
		return $this->parent == null;
	}
}
?>
