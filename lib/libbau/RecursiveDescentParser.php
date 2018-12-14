<?php
include_once('Parser.php');
include_once('ParseTable.php');

/*
* Eh, maybe eventually 
* 
* UNIMPLEMENTED
* @author: Bremen Braun
*/
class RecursiveDescentParser implements Parser {
	private $parseTable;
	private $symbolStack;
	
	public function __construct() {
		$this->parseTable  = new ParseTable();
		$this->symbolStack = array();
		 
		# Initialize the symbols stack
		array_push($this->symbolStack
	}
	
	public function parse($tokens) {

	}
}

?>
