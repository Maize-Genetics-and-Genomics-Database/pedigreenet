<?php
include_once('Parser.php');
include_once('Tokenizer.php');
include_once('BauplanParser.php');

/*
* Parser for Syntax Level 4
*
* Level 4 syntax uses words for blocks
* BEGIN for (
* END for )
* DIRSTART for {
* DIREND for }
*
* @author: Bremen Braun
*/
class Bauplan4Parser extends Parser implements Tokenizer {
	
	public function level() {
		return 4;
	}
	
	public function tokenize($source) {
		$tokens = array();
		foreach (preg_split('/\s+|\(|\)|{|}|;|=|"/', $source) as $token) {
			$typedToken;
			
			switch($token) {
				case 'Template':
					$typedToken = new Token($token, 'T_TEMPLATE_SIGIL');
					break;
				case 'Section':
					$typedToken = new Token($token, 'T_SECTION_SIGIL');
					break;
				case 'Code':
					$typedToken = new Token($token, 'T_CODE_SIGIL');
					break;
				case 'Variable':
					$typedToken = new Token($token, 'T_VARIABLE_SIGIL');
					break;
				case '(':
					$typedToken = new Token($token, 'T_DIRECTIVE_START');
					break;
				case ')':
					$typedToken = new Token($token, 'T_DIRECTIVE_END');
					break;
				case '{':
					$typedToken = new Token($token, 'T_TYPE_START');
					break;
				case '}':
					$typedToken = new Token($token, 'T_TYPE_END');
					break;
				case ';':
					$typedToken = new Token($token, 'T_DIRECTIVE_SEPARATOR');
					break;
				case '=':
					$typedToken = new Token($token, 'T_DIRECTIVE_KEYVAL_SEPARATOR');
					break;
				case '"':
					$typedToken = new Token($token, 'T_ENCAPSED_STRING');
					break;
				default:
					$typedToken = new Token($token, 'T_LITERAL');
					break;
			}
			
			array_push($tokens, $typedToken);
		}
		
		return $tokens;
	}
	
	public function parse($source) {
		return $this->parseTokens($this->tokenize($source));
	}
	
	public function parseTokens($tokens) {
		
	}
}
?>
