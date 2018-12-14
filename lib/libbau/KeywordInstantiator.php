<?php 
include_once('N1ary.php');
include_once('CallStack.php');

#FIXME: temporary fix until I find out how __autoload is supposed to work
include_once('UnlessEmpty.php');
include_once('IfEmpty.php');
include_once('LowerCase.php');
include_once('UpperCase.php');
include_once('IncrementInt.php');
include_once('DecrementInt.php');
include_once('IfEqual.php');
include_once('UnlessEqual.php');
include_once('Alt.php');
include_once('IfExists.php');
include_once('UnlessExists.php');
include_once('IfVariableEquals.php');
include_once('UnlessVariableEquals.php');
include_once('TestCond.php');
include_once('Not.php');

/*
 * The keyword type is a special type which provides basic logical operations for formatting
 */
class KeywordInstantiator extends N1ary {
	private $kwd;
	private $callstack;
	
	/*
	 * Create a new anonymous type whose keyword is value
	 */
	public function __construct($value) {
		parent::__construct('lambda');
		
		# Find the concrete type
		$class = $this->getConcreteClass($value); # find by naming convention
		$file  = $this->getClassFile($class);
		if (!$this->tryInclude($file)) {
			$this->_except("Factory instantiation failed for \"$value\" while trying to call a keyword: No such file ($file)");
		}
		
		# Set the keyword to be called by this node
		$this->kwd = new $class($this);
		
		# Instantiate the call stack
		$this->callstack = CallStack::instance();
	}
	
	public function _type() {
		return 'Keyword';
	}
	
	public function _file() {
		return "(Sorry, because of a PHP bug I can't find the filename for you)";
	}
	
	public function _keyword() {
		return $this->kwd;
	}
	
	public function _callstack() {
		return $this->callstack;
	}
	
	public function assemble() {
			return $this->kwd->assemble();
	}
	
	public function __autoload($class) {
		include_once("$class.php");
	}
	
	protected function createClone() {
		return new self($this->kwd->keyword());
	}
	
	private function getClassFile($class) {
		return "$class.php";
	}
	
	private function getConcreteClass($type) {
		$proto = ucfirst(strtolower($type));
		
		$class = "";
		$prev_was_hyphen = false;
		# a keyword may have hyphens
		foreach (preg_split('//', $proto) as $character) {
			if ($character == '-') {
				$prev_was_hyphen = true;
			}
			else {
				if ($prev_was_hyphen) {
					$class .= ucfirst($character);
					$prev_was_hyphen = false;
				}
				else {
					$class .= $character;
				}
			}
		}
		
		return $class;
	}
	
	private function tryInclude($file) {
		set_error_handler(array($this, 'handleError'));
		error_reporting(0);
		$error = include_once($file);
		restore_error_handler();
		
		return $error;
	}
	
	private function handleError() {
		return false;
	}
	
}
?>
