<?php
include_once('Resource.php');

/*
 * @author: Bremen Braun
 */
class DirectiveUnit extends Resource {
	private $operand;
	private $executed;
	
	public function __construct($key, $value, $operand=null) {
		parent::__construct($key, $value);
		$this->operand  = $operand;
		$this->executed = false;
	}
	
	public function executed() {
		return $this->executed;
	}
	
	public function operand($operand=null) {
		if ($operand != null) {
			$this->operand = $operand;
		}
		
		return $this->operand;
	}
	
	public function execute() {
		$this->operand->_executeDirective($this->key(), $this->value());
		$this->executed = true;
	}
}
?>
