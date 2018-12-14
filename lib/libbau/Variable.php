<?php
include_once('Unary.php');
include_once('Context.php');

/*
* The Variable type
*
* @author: Bremen Braun
*/
class Variable extends Unary {

	/* Signals */
	const BEFORE_REPLACE = 0;
	const AFTER_REPLACE = 1;

	private $required;
	private $readonly;
	private $protected;
	private $variable;
	private $var_replace_done;
	private $var_variable_id;
	private $isset;
	private $events;
	private $escape;

	public function __construct($value, $cvalue=null) {
		parent::__construct($value, $cvalue);
		$this->required  = false;
		$this->readonly  = false;
		$this->protected = false;
		$this->isset     = false;
		$this->escape    = false;
		$this->didEscape = false;
		$this->events    = array(); // key/value pairs

		# Support variable variables
		$this->variable         = false;
		$this->var_replace_done = false;
		$this->var_variable_id  = $value;
	}

	public function replace($replacement) {
		if ($this->readonly && $this->_isReference()) {
			$this->_except("Cannot change the value of a readonly variable");
		}
		if ($this->protected && $this->_isReference()) {
			if ($this->_context() == Context::PHP) {
				$this->_except("Cannot change the value of a protected variable in PHP context");
			}
		}

		if ($this->variable && !$this->var_replace_done) { # rename the variable rather than changing its value
			$this->_value($replacement);
			$this->var_replace_done = true;
		}
		else {
			$this->trigger(Variable::BEFORE_REPLACE, array($this, $replacement));
			if ($this->escape && !$this->didEscape) {
				$replacement = addslashes($replacement);
				$this->didEscape = true;
			}
			$this->child($replacement);
			$this->trigger(Variable::AFTER_REPLACE, array($this, $replacement));
		}

		$this->isset = true;
	}

	public function append($value) {
		if ($this->readonly && $this->_isReference()) {
			$this->_except("Cannot change the value of a readonly variable");
		}
		if ($this->protected && $this->_isReference()) {
			if ($this->_context() == Context::PHP) {
				$this->_except("Cannot change the value of a protected variable in PHP context");
			}
		}

		$this->replace($this->child() . $value);
	}

	public function value() {
		return $this->child();
	}

	public function escape() {
		$this->escape = true;
	}

	public function hasBeenSet() {
		return $this->isset;
	}

	public function assemble() {
		if ($this->required) {
			if ($this->child() == null) {
				$this->_except("Value required");
			}
		}

		return parent::assemble();
	}

	public function _type() {
		return 'Variable';
	}

	public function _required() {
		$this->required = true;
	}

	public function _readonly() {
		$this->readonly = true;
	}

	public function _protect() {
		$this->protected = true;
	}

	public function _variable() {
		$this->variable = true;
	}

	public function _global($truth=null) {
		if ($truth != null) {
			$this->isGlobal = $truth;
		}

		return $this->isGlobal;
	}

	public function _registerEvent($signal, $action, $name="") {
		if (!array_key_exists($signal, $this->events)) {
			$this->events[$signal] = array();
		}

		array_push($this->events[$signal], array(
			'action' => $action,
			'name'   => $name
		));
	}

	public function _unregisterEvent($signal, $name) {
		if (array_key_exists($signal, $this->events)) {
			$index = 0;
			foreach ($this->events[$signal] as $event) {
				if ($event['name'] == $name) {
					unset($this->events[$signal][$index]);
					return true; // unregister succeeded
				}
				$index++;
			}
		}
		return false; // unable to find event
	}

	protected function _addProperties($clonee) {
		$this->required  = $clonee->required;
		$this->readonly  = $clonee->readonly;
		$this->variable  = $clonee->variable;
		$this->escape    = $clonee->escape;
		$this->didEscape = $clonee->didEscape;
		if ($this->variable) {
			$clonee->var_replace_done = false;
			$clonee->_value($this->var_variable_id);
		}
	}

	private function trigger($signal, $args=array()) {
		if (array_key_exists($signal, $this->events)) {
			foreach ($this->events[$signal] as $event) {
				if (call_user_func_array($event['action'], $args) === false)
					break;
			}
		}
	}
}
?>
