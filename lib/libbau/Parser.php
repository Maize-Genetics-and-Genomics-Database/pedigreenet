<?php
include_once('Token.php');

/*
 * The current state of tokenization and parsing in Bauplan is gross (although this is offset by the compiler). This was meant to alleviate it
 * but I did not have time to implement it.
 * 
 * UNIMPLEMENTED
 * @author: Bremen Braun
 */
abstract class Parser {
	protected $file;
	protected $token;
	
	public function __construct($file="") {
		$this->file  = $file;
		$this->token = null;
	}
	
#	abstract public function parseLine($source);
	abstract public function parse($source);
	
	public function parseFile($file) {
		$this->file = $file;
		$source = file_get_contents($file);
		return $this->parse($source);
	}
	
	public function error($message, $give_line=true, $give_file=true) {
		$error = "Bauplan Parse Error: $message";
		if ($give_line) {
			if ($this->token != null) {
				$error .= " at line " . $this->token->line();
			}
		}
		if ($give_file) {
			if ($this->file != "") {
				$error .= " in file " . $this->file;
			}
		}
		
		die("$error\n");
	}
	
	public function file($file=null) {
		if ($file != null) {
			$this->file = $file;
		}
		
		return $this->file;
	}
}
?>
