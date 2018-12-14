<?php

/*
* Base class for defining a directive
* 
* @author: Bremen Braun
*/
abstract class Directive {
	private $alternatives; # only allow one alternative to be set
	private $executionPoint;
	private $executed;
	
	public function __construct($executionPoint) {
		$this->executionPoint = $executionPoint;
		$this->executed       = false;
	}
	
	public function executed() {
		return $this->executed;
	}
	
	/* Extender must implement these */
	abstract function execute();
}
?>
