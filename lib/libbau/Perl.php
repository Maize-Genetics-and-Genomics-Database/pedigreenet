<?php
include_once('Interpreter.php');

/*
 * Provide Perl support in Code sections. Requires Perl addon for PHP
 * 
 * UNIMPLEMENTED
 * @author: Bremen Braun
 */
class Perl extends Interpreter {
	private $interpreter;
	
	public function __construct($caller) {
		parent::__construct($caller);
		$this->interpreter = new Perl();
	}
	
	public function interpret($code) {
		return $perl->eval($code);
	}
}
?>
