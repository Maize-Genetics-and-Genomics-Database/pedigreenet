<?php 
include_once('Interpreter.php');

/*
 * Special language for configuring properties from owner
 * 
 * The syntax is a basic "key : value" where key is an identifier to do a lookup
 * on from the owner and value is the value to give it
 * 
 * TODO:
 *  1) Add escape characters (\)
 *  2) make more useful by allowing section loops like:
 *     - items(thing1, thing2): (name1, age1), (name2, age2), (name3, age3)
 */
class Configuration extends Interpreter {
	
	/*
	 * Configure properties from owner
	 */
	public function interpret($code) {
		$owner  = $this->reflect()->_owner();
		foreach ($this->parse($code) as $key => $value) {
			$var = $owner->get($key);
			if ($var->_type() != 'Variable') {
				$this->reflect()->_except("key \"$key\" provided in configuration is not of type Variable");
			}
			$owner->get($key)->replace($value);
		}
	}
	
	private function parse_new($code) {
		foreach ($this->tokenize($code) as $token) {
			
		}
	}
	
	private function tokenize($code) {
		$tokens = array();
		foreach (preg_split('//', $code) as $character) {
			
		}
		
		return $tokens;
	}
	private function parse($code) {
		$keyvals = array();
		$kv_list = preg_split('/\r\n|\r|\n/', $code, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($kv_list as $kv_string) {
			$key_val = preg_split('/:/', $kv_string);
			$key   = trim($key_val[0]);
			$value = "";
			if (count($key_val) > 1) {
				$value = trim($key_val[1]);
			}
			
			if ($key != "") {
				$keyvals[$key] = $value;
			}
		}
		
		return $keyvals;
	}
}
?>