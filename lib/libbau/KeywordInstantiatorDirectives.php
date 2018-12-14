<?php
include_once('Directives.php');

/*
 * Compile-time directives for Keyword types
 * 
 * @author: Bremen Braun
 */
class KeywordInstantiatorDirectives extends Directives {
	private $key;
	
	public function executeBody($key, $val) {
		$this->key = $key;
		
		switch($key) {
			case 'argument':
				$this->argument($val);
				break;
			
			# Aliases
			case 'arg':
				$this->argument($val);
				break;
			
			default:
				$this->_except("Unrecognized directive \"$key\"");
		}
	}
	
	private function argument($val) {
		if ($val == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}
		
		$this->caller->_keyword()->addArgument($val);
	}
}
?>
