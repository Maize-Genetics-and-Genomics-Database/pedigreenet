<?php
include_once('Unary.php');

#FIXME: temporary fix until I find out how __autoload is supposed to work
include_once('PHP.php');
include_once('Configuration.php');

/*
 * Type for allowing inline code
 * 
 * @author: Bremen Braun
 */
class Code extends Unary {
	private $interpreter;
	private $executed;
	
	public function __construct($identifier, $cval=null, $interpreter=null) {
		parent::__construct($identifier, $cval);
		if ($interpreter == null) {
			$this->interpreter = new PHP($this);		
		}
		else {
			$this->interpreter = $interpreter;
		}
		
		$this->executed = false;
	}
	
	public function _type() {
		return 'Code';
	}
	
	public function _code($code=null) {
		if ($code != null) {
			$this->child($code);
		}
		
		return $this->child();
	}
	
	public function _interpreter($interpreter=null) {
		if ($interpreter != null) {
			$this->interpreter = $interpreter;
		}
		
		return $this->interpreter;
	}
	
	public function execute() {
		$this->executed = true;
		return $this->interpreter->interpret($this->child());
	}
	
	public function isExecuted() {
		return $this->executed;
	}
	
	public function assemble() {
		if (!$this->executed) {
			return $this->execute();
		}
	}
	
	public function __autoload($class) {
		include_once("$class.php");
	}
}
?>
