<?php
/*
* Parse directives are called by double-dollar $$
* 
* @author: Bremen Braun
*/
class PreprocessorDirectives {
	private $preprocessor;
	
	public function __construct($preprocessor) {
		$this->preprocessor = $preprocessor;
	}
	
	public function execute($key, $val) {
		switch($key) {
			case 'SYNTAX-LEVEL':
				$this->syntaxLevel($val);
				break;
			case 'DEFINE':
				$this->define($val); # here val should be an array
				break;
#			case 'ESCAPE-UNTIL':
#				$this->escapeUntil($val);
#				break;
			default:
				$this->except("Unrecognized parse directive \"$key\"");
		}
	}
	
	private function syntaxLevel($levels) {
		if (count($levels) != 1) {
			$this->except("Parse directive \"SYNTAX-LEVEL\" requires a single argument");
		}
		
		$parser = null;
		$level  = $levels[0];
		if ($level == 1) {
			include_once('Bauplan1Parser.php');
			$parser = new Bauplan1Parser();
		}
		elseif ($level == 2) {
			include_once('Bauplan2Parser.php');
			$parser = new Bauplan2Parser();
		}
		elseif ($level == 3) {
			include_once('BauplanCustomParser.php');
			$parser = new BauplanCustomParser();
		}
		else {
			$this->except("Unknown syntax level \"$level\". Currently only level 1, 2, and 3 are available");
		}
		
		#TODO
		$this->preprocessor->parser($parser);
	}
	
	private function define($array) {
		$symkey = $array[0];
		$symval = $array[1];
		
		$parser = $this->preprocessor->parser();
		if ($parser->level() != 3) {
			$this->except("DEFINE is only available with SYNTAX-LEVEL 3");
		}
		if (strlen($symval) > 1) {
			$this->except("DEFINE only allows single character symbols");
		}
		if (strlen($symkey) < 1) {
			$this->except("DEFINE must be given a key");
		}
		
		switch($symkey) {
			case 'TEMPLATE-SIGIL':
				$parser->templateSigil($symval);
				break;
			case 'SECTION-SIGIL':
				$parser->sectionSigil($symval);
				break;
			case 'CODE-SIGIL':
				$parser->codeSigil($symval);
				break;
			case 'VARIABLE-SIGIL':
				$parser->variableSigil($symval);
				break;
			case 'TYPE-START':
				$parser->typeStart($symval);
				break;
			case 'TYPE-END':
				$parser->typeEnd($symval);
				break;
			case 'DIRECTIVE-START':
				$parser->directiveStart($symval);
				break;
			case 'DIRECTIVE-END':
				$parser->directiveEnd($symval);
				break;
			case 'DIRECTIVE-KV-SEPARATOR':
				$parser->directiveKeyValSeparator($symval);
				break;
			case 'DIRECTIVE-SEPARATOR':
				$parser->directiveSeparator($symval);
				break;
			default:
				$this->except("Unknown parse symbol \"$symkey\". Understood symbols are TEMPLATE-SIGIL, SECTION-SIGIL, CODE-SIGIL, VARIABLE-SIGIL, TYPE-START, TYPE-END, DIRECTIVE-START, DIRECTIVE-END, DIRECTIVE-KV-SEPARATOR, and DIRECTIVE-SEPARATOR");
		}
	}
	
	private function escapeUntil($literal) {
		
	}
	
	private function except($string) {
		$this->preprocessor->error($string, false);
		die("$string\n");
	}
}
?>
