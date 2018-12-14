<?php
include_once('Directives.php');

/*
 * Compile-time directives for Code types
 * 
 * @author: Bremen Braun
 */
class CodeDirectives extends Directives {	
	private $key;
	
	public function executeBody($key, $val) {
		$this->key = $key;
		
		switch($key) {
			case 'language':
				$this->language($val);
				break;
			case 'lazy':
				$this->lazy($val);
				break;
			case 'eager':
				$this->eager($val);
				break;
			case 'external':
				$this->external($val);
				break;
			
			# Aliases
			case 'lang':
				$this->language($val);
				break;
			case 'extern':
				$this->external($val);
				break;
				
			# Unimplemented directives
			case 'allow-native':
				$this->_except("Unimplemented Code directive \"allow-native\"");
				$this->allowNative($val);
				break;
			case 'import':
				$this->_except("Unimplemented Code directive \"import\"");
				$this->import($val);
				break;
			case 'export':
				$this->_except("Unimplemented Code directive \"export\"");
				$this->export($val);
				break;
			
			default:
				$this->_except("Unrecognized directive \"$key\"");
		}
	}
	
	private function language($val) {
		if ($val == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}
		
		$interpreter;
		$language = strtolower($val);
		switch($language) {
			case 'php':
				include_once('PHP.php');
				$interpreter = new PHP($this->caller);
				break;
			case 'configuration':
				include_once('Configuration.php');
				$interpreter = new Configuration($this->caller);
				break;
			default:
				$this->_except("Can't find interpreter for language $val");
		}
		
		$this->caller->_interpreter($interpreter);
	}
	
	private function lazy($val) {
		if ($val != null) {
			$this->_except("Directive \"" . $this->key . "\" does not accept an argument");
		}
		
		# This is the default so nothing to do!
	}
	
	private function eager($val) {
		if ($val != null) {
			$this->_except("Directive \"" . $this->key . "\" does not accept an argument");
		}
		
		$this->caller->execute();
	}
	
	private function external($val) {
		if ($val == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}
		
		if (!file_exists($val)) {
			$this->_except("Could not open external " . get_class($this->caller->_interpreter()) . " code file $val");
		}
		$code = file_get_contents($val);
		$this->caller->_code($code);
	}
	
	/*
	 * Parse code as bauplan before running interpreter
	 */
	private function allowNative($val) {
		if ($val != null) {
			$this->_except("Directive \"" . $this->key . "\" does not accept an argument");
		}
		
		$execution_point = $this->precondition();
		$this->precondition(true);
		$unparsed = $this->caller->_code();
		
		include_once('Loader.php');
		$loader = new Loader();
		
		$parsed = $loader->loadSource($unparsed, "TEST-TEST-TEST");
		$parsed   = $parser->parseSource($unparsed);
		$executed = $parsed->assemble();
		$this->caller->_code($executed);
		
		$this->precondition($execution_point);
	}
	
	/*
	 * Export the current code section to be run later
	 */
	private function export($val) {
		if ($val == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}
		
		
	}
	
	/*
	 * Import a previously exported code section to run at the current point
	 */
	private function import($val) {
		if ($val == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}
		
		
	}
}
?>
