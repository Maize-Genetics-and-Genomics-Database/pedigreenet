<?php

/*
* Basis for Bauplan types, implemented as cons cells (this is immutable)
* 
* UNIMPLEMENTED - basis is currently tree, not S-expression
* @author: Bremen Braun
*/
class SExpression {
	private $car; # atom
	private $cdr; # s-expression
	
	/*
	* Create the base S-Expression as an atom
	*/
	function __construct($car) {
		$this->car = $car;
		$this->cdr = null; # atomic has no cdr
	}
	
	function cons($sexp) { #FIXME: implicitly creates a proper list, should only be definable via NIL cdr
		$rexp = new SExpression($this->car);
		$rexp->cdr = $sexp;
		
		return $rexp;
	}
	
	function car() {
		if ($this->cdr == null) {
			throw new Exception("Wrong type (object is atomic)");
		}
		
		return $this->car;
	}
	
	function cdr() {
		if ($this->cdr == null) {
			throw new Exception("Wrong type (object is atomic)");
		}
		
		return $this->cdr;
	}

	/*
	* A list is improper if the last cdr is not null
	*/
	function isProper() {
		return true; # improper lists aren't supported
	}
		
	function __toString() {
		return $this->toStringHelper($this);
	}
	
	private function toStringHelper($sexp) {
		if ($sexp->cdr == null) { # atomic
			return $sexp->car;
		}
		else {
			return $sexp->car . " " . $sexp->toStringHelper($sexp->cdr);
		}
	}
}
?>
