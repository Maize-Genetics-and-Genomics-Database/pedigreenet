<?php 

/*
 * Methods a concrete keyword must implement
 * 
 * author: Bremen Braun
 * 
 * TODO: Better formalism
 */
abstract class Keyword {
	private $isBeta;
	private $typenode;
	private $arguments;
	
	public function __construct($typenode) {
		$this->isBeta    = false;
		$this->typenode  = $typenode;
		$this->arguments = array(); 
	}
	
	/*
	 * Return the node this keyword belongs to
	 */
	public function typenode() {
		return $this->typenode;
	}
	
	/*
	 * Return all unary types contained within the typenode
	 */
	public function unaries() {
		$unaries = array();
		foreach ($this->typenode->_children() as $child) {
			if ($child->_type() == 'Code' || $child->_type() == 'Variable') {
				array_push($unaries, $child);
			}
		}
		
		return $unaries;
	}
	
	/*
	 * Return all unary and n1ary types contained within the typenode
	 */
	public function n1aries() {
		$n1aries = array();
		foreach ($this->typenode->_children() as $child) {
			if ($child->_type() == 'Code' || $child->_type() == 'Variable' || $child->_type() == 'Keyword') {
				array_push($n1aries, $child);
			}
		}
		
		return $n1aries;
	}
	
	public function addArgument($arg) {
		array_push($this->arguments, $arg);
	}
	
	public function arguments() {
		return $this->arguments;
	}
	
	public function isBeta() {
		$this->isBeta = true;
	}
	
	/*
	 * Inside-out evaluation for nested keywords
	 */
	public function assemble() {
		$typenode = $this->typenode();
		# Only allow beta features if explicitly enabled
		if ($this->isBeta) {
			$owner = $typenode->_owner();
			if (!$owner->_betaEnabled()) {
				$typenode->_except("Keyword \"" . $this->keyword() . "\" is a beta feature. To use this feature you must call directive \"enable-beta\" in Template " . $owner->identifier() . ". Please note that beta features are subject to change");
			}
		}
		
		$output = "";
		foreach ($typenode->_children() as $child) {
			if ($child->_type() == 'Keyword') {
				$keyword = $child->_keyword();
				
				$output .= $keyword->assemble();
				$output = $keyword->evaluate($output);
			}
			else {
				$output .= $child->assemble();
			}
		}
		
		return $this->evaluate($output);
	}
	
	abstract public function keyword();
	abstract public function evaluate($input);
}
?>
