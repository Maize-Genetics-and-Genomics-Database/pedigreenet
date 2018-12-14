<?php
include_once('Type.php');

/*
* An abstract class for a type which supports nesting
*
* @author: Bremen Braun
*/
abstract class Nary extends Type {
	
	public function get($identifier) {
		$return = $this->getHelper($this, $identifier);
		if ($return == null) {
			$this->_except("No such identifier \"$identifier\" in call to get()");
		}
		
		return $return;
	}
	
	public function has($identifier) {
		$return = $this->getHelper($this, $identifier);
		if ($return == null) {
			return false;
		}
		
		return true;
	}
	
	/*
	* Even when a concrete Nary class is a leaf it's not because its
	* contents should never be published
	*/
	public function _isLeaf() {
		return false;
	}
	
	/*
	* Enforce template-level scoping
	*
	*/
	public function _addChild($tree) {
		# Consult owner's symbol table to see if $tree should be a reference
		$symbols   = $this->_scope()->_symbols();
		$reference = $symbols->get($tree->identifier());

		if ($reference != null) { # use reference
			if ($reference->_type() != 'Variable') { # this is admittedly sloppy...
				$this->_except("Duplicate declaration of " . $tree->identifier());
			}
			
			$tree = $reference;
		}
		else { # use new instance
			$tree->_owner($this->_owner());
			$tree->_onAdd($this);
			
			$symbols->add(new Resource($tree->identifier(), $tree));
		}
		
		return parent::_addChild($tree);
	}
	
	# Private functions
	private function getHelper($node, $target) {
		foreach ($node->_children() as $child) {
			if (!$child->_isLeaf()) {
				if ($child->_value() == $target) {
					return $child;
				}
				
				$val = $this->getHelper($child, $target);
				if ($val != null) {
					return $val;
				}
			}
		}
	}
}
?>
