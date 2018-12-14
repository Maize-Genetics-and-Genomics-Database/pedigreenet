<?php
include_once('Parser.php');
include_once('Tokenizer.php');
include_once('BauplanParser.php');

/*
* Parser for Syntax Level 3, the customizable level
*
* By default, level 3 syntax is the same as level 2 with the additional ability to set
* the symbols for each token
* 
* @author: Bremen Braun
*/
class BauplanCustomParser extends Parser, Tokenizer {
	private $template_sigil;
	private $section_sigil;
	private $code_sigil;
	private $variable_sigil;
	private $keyword_sigil;
	private $type_start;
	private $type_end;
	private $directive_start;
	private $directive_end;
	private $directive_keyval_separator;
	private $directive_separator;
	
	public function __construct($file="") {
		parent::__construct($file);
		
		$this->template_sigil             = '*';
		$this->section_sigil              = '@';
		$this->code_sigil                 = '&';
		$this->variable_sigil             = '$';
		$this->keyword_sigil              = '#';
		$this->type_start                 = '(';
		$this->type_end                   = ')';
		$this->directive_start            = '{';
		$this->directive_end              = '}';
		$this->directive_keyval_separator = ':';
		$this->directive_separator        = '|';
	}
	
	public function level() {
		return 3;
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
	
	public function keywordSigil($sigil=null) {
		if ($sigil != null) {
			$this->keyword_sigil = $sigil;
		}
		
		return $this->keyword_sigil;
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
	
	public function directiveKeyValSeparator($symbol=null) {
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
		if ($this->duplicateSigils()) {
			die("Bauplan Parse Error: Can't use the same sigil for more than one type\n");
		}
		
		#################
		#     Inits     #
		#################
		$token  = "";
		$prev   = "";
		$line   = 1;
		$tokens = array();
		$prev_token = new Token("", "");
		
		##################
		#     States     #
		##################
		$begin           = true;
		$in_html         = false;
		$in_code         = false;
		$in_code_section = false;
		$in_directive    = false;
		$in_comment      = false;
		
		$characters = preg_split('//', $source);
		for ($i = 0; $i < count($characters) - 1; $i++) {
			$curr = $characters[$i];
			$next = $characters[$i+1];
			
		
			if ($i > 0) {
				$prev = $characters[$i-1];
			}
			if (!$in_comment) { # don't parse inside of comments
				if ($prev != "\\") { # not the escape character
					#################
					#     Types     #
					#################
					if ($curr == $this->template_sigil) { # Template
						if ($next == $this->type_start) {
							if ($begin) { # for heading comment
								$begin = false;
								$token = "";
							}
							elseif (strlen($token) > 0) {
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
								$token = "";
							}
							$tmpl = new Token($this->template_sigil . $this->type_start, 'T_TMPL', $line);
							
							array_push($tokens, $tmpl);
							$prev_token = $tmpl;
							$i++;
							
							$in_html = false;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->section_sigil) { # Section
						if ($next == $this->type_start) {
							if (strlen($token) > 0) {
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
								$token      = "";
							}
							$sctn = new Token($this->section_sigil . $this->type_start, 'T_SCTN', $line);
							
							array_push($tokens, $sctn);
							$prev_token = $sctn;
							$i++;
							
							$in_html = false;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->code_sigil) { # Code
						if ($next == $this->type_start) {
							if (strlen($token) > 0) {
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
								$token = "";
							}
							$code = new Token($this->code_sigil . $this->type_start, 'T_CODE', $line);
							
							array_push($tokens, $code);
							$prev_token = $code;
							$i++;
							
							$in_code_section = true;
							$in_html         = false;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->variable_sigil) { # Variable
						if ($next == $this->type_start) {
							if (strlen($token) > 0) {
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
								$token = "";
							}
							$variable = new Token($this->variable_sigil . $this->type_start, 'T_VRBL', $line);
							
							array_push($tokens, $variable);
							$prev_token = $variable;
							$i++;
							
							$in_html = false;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->keyword_sigil) { # Keyword
						if ($next == $this->type_start) {
							if (strlen($token) > 0) {
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
								$token = "";
							}
							$keyword = new Token('#(', 'T_KWD', $line);
							
							array_push($tokens, $keyword);
							$prev_token = $keyword;
							$i++;
							
							$in_html = false;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->type_end) { # End type
						if (strlen($token) > 0) {
							if (in_array($prev_token->type(), array('T_VRBL', 'T_TMPL', 'T_SCTN', 'T_CODE'))) { # Support empty bodies
								$ident = new Token($token, 'IDENT', $line);
								
								array_push($tokens, $ident);
								$prev_token = $ident;
							}
							else {
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
							}
							$token = "";
						}
						if ($in_code_section) {
							$in_code_section = false;
							$in_code = false;
						}
						
						$close = new Token($this->type_end, 'CLOSE', $line);
						
						array_push($tokens, $close);
						$prev_token = $close;
						$in_html = true;
					}
					
					######################
					#     Directives     #
					######################
					elseif ($curr == $this->directive_start) { # Directive start
						if (strlen(trim($token)) == 0 && $prev_token->type() == 'IDENT') {
							$dirblock = new Token($this->directive_start, 'DIR_START', $line);
							
							array_push($tokens, $dirblock);
							$prev_token = $dirblock;
							$in_directive = true;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->directive_end) { # Directive end
						if ($in_directive) { # Not a special token if outside a directive
							if (strlen($token) > 0) {
								$directive = null;
								if ($prev_token->type() == 'DIR_KEY') {
									$directive = new Token(trim($token), 'DIR_VAL', $line);
								}
								else {
									$directive = new Token(trim($token), 'DIR_KEY', $line);
								}
								
								array_push($tokens, $directive);
								$prev_token = $directive;
								$token = "";
							}
							$dirblock = new Token($this->directive_end, 'DIR_END', $line);
							
							array_push($tokens, $dirblock);
							$prev_token   = $dirblock;
							$in_directive = false;
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->directive_keyval_separator) { # Key, value separator
						if ($in_directive) {
							$dir_key = new Token(trim($token), 'DIR_KEY', $line);
							
							array_push($tokens, $dir_key);
							$prev_token = $dir_key;
							$token = "";
						}
						else {
							$token .= $curr;
						}
					}
					elseif ($curr == $this->directive_separator) { # Directive separator
						if ($in_directive) {
							if (strlen($token) > 0) {
								$dir_kv = null;
								if ($prev_token->type() == 'DIR_KEY') {
									$dir_kv = new Token(trim($token), 'DIR_VAL', $line);
								}
								else {
									$dir_kv = new Token(trim($token), 'DIR_KEY', $line);
								}
								
								array_push($tokens, $dir_kv);
								$prev_token = $dir_kv;
								$token = "";
							}
							$dir_sep = new Token($this->directive_separator, 'DIR_SEP', $line);
							
							array_push($tokens, $dir_sep);
							$prev_token = $dir_sep;
						}
						else {
							$token .= $curr;
						}
					}
					
					####################
					#     Comments     #
					####################
					elseif ($curr == ';') { # Comment is ;;
						if ($prev == ';') {
							if (strlen($token) > 0) {
								$html = null;
								if ($in_code) {
									$html = new Token($token, 'CODE', $line);
								}
								else {
									$html = new Token($token, 'HTML', $line);
								}
								
								array_push($tokens, $html);
								$prev_token = $html;
								$token = "";
							}
							
							$in_comment = true;
						}
						else {
							$token .= $curr;
						}
					}
					
					######################
					#     Whitespace     #
					######################
					elseif (in_array($curr, array(' ', "\t", "\n"))) {
						if ($curr == "\n") {
							$line++;
						}
						
						if ($in_html || $in_code) { # is whitespace part of token?
							$token .= $curr;
						}
						else { # whitespace is a token separator
							if (strlen($token) > 0) {
								if ($begin) {
									$token .= $curr;
									$in_html = true;
								}
								else {
									$ident = null;
									if ($prev_token->type() == 'CLOSE') {
										if ($in_code) {
											$ident = new Token($token, 'CODE', $line);
										}
										else {
											$ident = new Token($token, 'HTML', $line);
										}
									}
									else {
										$ident = new Token($token, 'IDENT', $line);
									}
									
									array_push($tokens, $ident);
									$prev_token = $ident;
									$token = "";
									
									if ($in_code_section) {
										$in_code = true;
									}
									else {
										$in_html = true;
									}
								}
							}
						}
					}
					
					##################
					#     Escape     #
					##################
					elseif ($curr == "\\") {
						# skip until next iteration
					}
					else {
						$token .= $curr;
					}
				}
				else { # was the escape character
					$token .= $curr;
				}
			}
			else { # inside of comment
				if ($curr == "\n") { # no multiline comments
					$line++;
					$in_comment = false;
				}
			}
		}
				
		return $tokens;
	}
	
	public function parse($source) {
		$parser = new BauplanParser();
		return $parser->parseTokens($this->tokenize($source));
	}
	
	private function duplicateSigils() {
		$symbol_array = array(
			$this->template_sigil,
			$this->section_sigil,
			$this->code_sigil,
			$this->variable_sigil,
			$this->type_start,
			$this->type_end,
			$this->directive_start,
			$this->directive_end,
			$this->directive_keyval_separator,
			$this->directive_separator
		);
		
		if (count($symbol_array) != count(array_unique($symbol_array))) {
			return true; # there are duplicates
		}
		
		return false; # no duplicates
	}
}
?>
