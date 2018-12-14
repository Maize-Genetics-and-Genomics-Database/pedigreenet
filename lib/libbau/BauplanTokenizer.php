<?php
include_once('Tokenizer.php');
include_once('Tokens.php');
include_once('Token.php');

/*
* Bauplan Lexical Scanner
*
* Split Bauplan source into tokens
* 
* @author: Bremen Braun
*/
class BauplanTokenizer extends Tokenizer {
	private $template_sigil;
	private $section_sigil;
	private $variable_sigil;
	private $code_sigil;
	private $type_start;
	private $type_end;
	private $directive_start;
	private $directive_end;
	private $directive_separator;
	private $directive_keyval_separator;
	private $typemap;
	
	public function __construct() {
		# Default symbols
		$this->template_sigil             = '*';
		$this->section_sigil              = '@';
		$this->variable_sigil             = '$';
		$this->code_sigil                 = '&';
		$this->type_start                 = '(';
		$this->type_end                   = ')';
		$this->directive_start            = '{';
		$this->directive_end              = '}';
		$this->directive_separator        = '|';
		$this->directive_keyval_separator = ':';
		
		# Map all redefinable types
		$this->typemap = array(
			$this->template_sigil             => T_TEMPLATE_SIGIL,
			$this->section_sigil              => T_SECTION_SIGIL,
			$this->variable_sigil             => T_VARIABLE_SIGIL,
			$this->code_sigil                 => T_CODE_SIGIL,
			$this->type_start                 => T_TYPE_START,
			$this->type_end                   => T_TYPE_END,
			$this->directive_start            => T_DIRECTIVE_START,
			$this->directive_end              => T_DIRECTIVE_END,
			$this->directive_separator        => T_DIRECTIVE_SEPARATOR,
			$this->directive_keyval_separator => T_DIRECTIVE_KEYVAL_SEPARATOR,
		);
	}
	
	public function templateSigil($sigil=null) {
		if ($sigil != null) {
			$this->template_sigil = $sigil;
		}
		
		return $this->template_sigil;
	}
	
	public function sectionSigil($sigil=null) {
		if ($sigil != null) {
			$this->section_sigil = $sigil;
		}
		
		return $this->section_sigil;
	}
	
	public function codeSigil($sigil=null) {
		if ($sigil != null) {
			$this->code_sigil = $sigil;
		}
		
		return $this->section_sigil;
	}
	
	public function variableSigil($sigil=null) {
		if ($sigil != null) {
			$this->variable_sigil = $sigil;
		}
		
		return $this->variable_sigil;
	}
	
	public function typeStart($symbol=null) {
		if ($symbol != null) {
			$this->type_start = $symbol;
		}
		
		return $this->type_start;
	}
	
	public function typeEnd($symbol=null) {
		if ($symbol != null) {
			$this->type_end = $symbol;
		}
		
		return $this->type_end;
	}
	
	public function directiveStart($symbol=null) {
		if ($symbol != null) {
			$this->directive_start = $symbol;
		}
		
		return $this->directive_start;
	}
	
	public function directiveEnd($symbol=null) {
		if ($symbol != null) {
			$this->directive_end = $symbol;
		}
		
		return $this->directive_end;
	}
	
	public function directiveKeyvalSeparator($symbol=null) {
		if ($symbol != null) {
			$this->directive_keyval_separator = $symbol;
		}
		
		return $this->directive_keyval_separator;
	}
	
	public function directiveSeparator($symbol=null) {
		if ($symbol != null) {
			$this->directive_separator = $symbol;
		}
		
		return $this->directive_separator;
	}
	
	public function tokenize($source) {
		if ($this->duplicateSymbol()) {
			throw new Exception("Duplicate symbols given for tokens");
		}
		
		$tokens = array();
		
		# Build tokenizer regex
		$regex_chars = array(
			'\s+',
			preg_quote($this->template_sigil),
			preg_quote($this->section_sigil),
			preg_quote($this->variable_sigil),
			preg_quote($this->code_sigil),
			preg_quote($this->type_start),
			preg_quote($this->type_end),
			preg_quote($this->directive_start),
			preg_quote($this->directive_end),
			preg_quote($this->directive_separator),
			preg_quote($this->directive_keyval_separator),
		);
		
		$regex = join('|', $regex_chars);
		$char_tokens = preg_split($source, "/$regex/");
		foreach ($char_tokens as $char_token) {
			switch($char_token) {
				$token_type = "";
				
				# Reassignable types
				case $this->template_sigil:
					$token_type = 'T_TEMPLATE_SIGIL';
					break;
				case $this->section_sigil:
					$token_type = 'T_SECTION_SIGIL';
					break;
				case $this->variable_sigil:
					$token_type = 'T_VARIABLE_SIGIL';
					break;
				case $this->code_sigil:
					$token_type = 'T_CODE_SIGIL';
					break;
				case $this->type_start:
					$token_type = 'T_TYPE_START';
					break;
				case $this->type_end:
					$token_type = 'T_TYPE_END';
					break;
				case $this->directive_start:
					$token_type = 'T_DIRECTIVE_START';
					break;
				case $this->directive_end:
					$token_type = 'T_DIRECTIVE_END';
					break;
				case $this->directive_separator:
					$token_type = 'T_DIRECTIVE_SEPARATOR';
					break;
				case $this->directive_keyval_separator:
					$token_type = 'T_DIRECTIVE_KEYVAL_SEPARATOR';
					break;
					
				# Others
				default:
					$token_type = 'T_LITERAL';
					
				array_push($tokens, new Token($char_token, $token_type));
			}
			if ($char_token == $this->template_sigil) {
				array_push($tokens, new Token($char_token, 'T_TEMPLATE_SIGIL'));
			}
		}
		
		return $tokens;
	}
	
	private function duplicateSymbol() {
		$symbols = array(
			$this->template_sigil,
			$this->section_sigil,
			$this->variable_sigil,
			$this->code_sigil,
			$this->type_start,
			$this->type_end,
			$this->directive_start,
			$this->directive_end,
			$this->directive_separator,
			$this->directive_keyval_separator,
		);
		
		if (count($symbol_array) != count(array_unique($symbol_array))) {
			return true; # there are duplicates
		}
		
		return false; # no duplicates
	}
}
?>
