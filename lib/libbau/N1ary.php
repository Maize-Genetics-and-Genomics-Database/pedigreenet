<?php
include_once('Nary.php');

/*
 * Base class for types which can hold only Unary types
 */
abstract class N1ary extends Nary {
	public function _addChild($tree) {
		$type = $tree->_type();
		if ($type != 'Variable' && $type != 'Code' && $type != 'HTML' && $type != 'Keyword') { #FIXME: this is gross!
			$this->_except("Attempt to add non-unary type \"" . $tree->identifier() . "\" of type " . $tree->_type() . " to N1-ary type " . $this->_type());
		}
		
		return parent::_addChild($tree);
	}
}
?>