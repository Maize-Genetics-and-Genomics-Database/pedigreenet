<?php
include_once('Conditional.php');

/*
 * Test equality of 2 or more arguments
 */
class IfEqual extends Conditional {
	private $cache = null;
	private $bool  = false;

	public function conditionalAssemble() {
		if ($this->cache == null) { # In case this is being called by evaluate, cache the result so it won't have to be recalculated
			$typenode = $this->typenode();
			$output = "";

			$arguments = $this->arguments();
			if (count($arguments) < 2) { # need at least 2 arguments
				$typenode->_except("Keyword " . $this->keyword() . " requires at least two arguments");
			}

			$first_value = null;
			$passed = false;
			$symbols  = $typenode->_scope(); # Check arguments from symbol table
			foreach ($arguments as $argument) {
				$symbol = $symbols->get($argument);
				if ($symbol == null) {
					$typenode->_except("[if-equal] Symbol table lookup failed on argument \"$argument\"");
				}

				if ($first_value == null) { # setting for comparison
					$first_value = $symbol->value();
				}
				else { // 2nd ... n variable
					$passed = true;
					if ($first_value == "" || strtolower($symbol->value()) != strtolower($first_value)) {
						return; # Fail; not equal
					}
				}
			}

			if ($passed) {
				$this->bool = true;
				foreach ($typenode->_children() as $child) {
					$output .= $child->assemble();
				}

				$this->cache = $output;
			}
		}

		return $this->cache;
	}

	public function boolEvaluate() {
		$this->conditionalAssemble();
		return $this->bool;
	}

	public function keyword() {
		return "if-equal";
	}
}
?>
