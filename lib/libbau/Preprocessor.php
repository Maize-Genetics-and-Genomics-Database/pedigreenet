<?php
include_once('Parser.php');
include_once('Tokenizer.php');
include_once('Token.php');
include_once('Bauplan1Parser.php'); # the default parser
include_once('PreprocessorDirectives.php');

# Types need to be included here for unserialize()
include_once('Template.php');
include_once('Section.php');
include_once('Code.php');
include_once('Variable.php');
include_once('HTML.php');
include_once('SymbolTable.php'); # Variables only

/*
* Scan a source file for any $$ and execute them
* 
* @author: Bremen Braun
*/
class Preprocessor extends Parser implements Tokenizer {
	private $parser;
	private $directives;
	
	public function __construct($file="") {
		parent::__construct($file);
		$this->parser     = new Bauplan1Parser($this->file);
		$this->directives = new PreprocessorDirectives($this);
	}
	
	/*
	* The only tokens the preprocessor understands are $$ followed by the name of the directive and the argument to the directive
	*/
	public function tokenize($source) {
		$tokens = array();
		$token  = "";
		$prev   = "";
		$line   = 1;
		
		$characters = preg_split('//', $source);
		for ($i = 0; $i < count($characters) - 1; $i++) {
			$curr = $characters[$i];
			$next = $characters[$i+1];
			
			if ($i > 0) {
				$prev = $characters[$i-1];
			}
			if ($prev != "\\") { # not the escape character
				if ($curr == '$') {
					if ($next == '$') {
						$init_token = new Token('$$', 'T_PREPROCINIT', $line);
						array_push($tokens, $init_token);
						
						$this->token = $token;
					}
				}
				elseif(in_array($curr, array(' ', "\t", "\n"))) { # any whitespace character
					$literal_token = new Token($token, 'T_LITERAL', $line);
					array_push($tokens, $literal_token);
					if ($curr == "\n") {
						$line++;
						
						# newline tokens are also important for multiarg parse directives
						$literal_token = new Token("\n", 'T_NEWLINE', $line);
						array_push($tokens, $literal_token);
					}
					
					$this->token = $literal_token;
					$token = "";
				}
				else {
					$token .= $curr;
				}
			}
			else { # previous character was escape, build current token with literal
				$token .= $curr;
			}
		}
		
		# push on a newline in case there's a directive at the bottom without a newline
		array_push($tokens, new Token("\n", 'T_NEWLINE', $line));
		return $tokens;
	}
	
	public function parse($source) {
		$tokenIterator = new TokenIterator($this->tokenize($source));
		while ($tokenIterator->hasNextToken()) {
			$token = $tokenIterator->nextToken();
			
			switch($token->type()) {
				case 'T_PREPROCINIT':
					$directive_key = $tokenIterator->nextToken();
					
					if ($directive_key->type() != 'T_LITERAL') {
						$this->error("Expected T_LITERAL, saw \"" . $directive_key->symbol() . "\" of type " . $directive_key->type());
					}
					
					# build up directive vals until T_NEWLINE
					$directive_args = array();
					$directive_val = $tokenIterator->nextToken();
					while ($directive_val->type() != 'T_NEWLINE') {
						array_push($directive_args, $directive_val->symbol());
						$directive_val = $tokenIterator->nextToken();
					}
					
					# Execute directives
					$this->directives->execute($directive_key->symbol(), $directive_args);
					break;
				default:
					# nothing to do
					break;
			}
		}
		
		return $this->parser->parse($source); # Parse the tokens with the real parser
	}
	
	public function parser($parser=null) {
		if ($parser != null) {
			$this->parser = $parser;
			$parser->file($this->file());
		}
		
		return $this->parser;
	}
}
?>
