<?php

/*
 * Token unit for parsing
 * 
 * @author: Bremen Braun
 */
class Token {
	private $symbol;
	private $type;
	
	public function __construct($symbol, $type, $line=-1) {
		$this->symbol = $symbol;
		$this->type   = $type;
		$this->line   = $line;
	}
	
	public function symbol($symbol=null) {
		if ($symbol != null) {
			$this->symbol = $symbol;
		}
		
		return $this->symbol;
	}
	
	public function type($type=null) {
		if ($type != null) {
			$this->type = $type;
		}
		
		return $this->type;
	}
	
	public function line($line=null) {
		if ($line != null) {
			$this->line = $line;
		}
		
		return $this->line;
	}
}

/*
 * Provide iteration methods for token collections
 * 
 * @author: Bremen Braun
 */
class TokenIterator {
	private $tokens;
	private $index;
	
	public function __construct($tokens) {
		$this->tokens = $tokens;
		$this->index  = 0;
	}
	
	public function reset() {
		$this->index = 0;
	}
	
	public function seek($index) {
		if ($index < 0 or $index >= count($this->tokens)) {
			return false;
		}
		
		$this->index = $index;
		return true;
	}
	
	public function hasNextToken() {
		return $this->index < count($this->tokens) ? 1 : 0;
	}
	
	public function hasPrevToken() {
		return $this->index > 0;
	}
	
	public function nextToken() {
		if (!$this->hasNextToken()) {
			return null;
		}
		
		return $this->tokens[$this->index++];
	}
	
	public function peekNextToken() {
		if (!$this->hasNextToken()) {
			return null;
		}
		
		return $this->tokens[$this->index];
	}
	
	public function prevToken() {
		if (!$this->hasPrevToken()) {
			return null;
		}
		
		return $this->tokens[--$this->index];
	}
	
	public function currentToken() {
		return $this->tokens[$this->index - 1];
	}	
}
?>
