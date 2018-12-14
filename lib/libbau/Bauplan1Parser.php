<?php
include_once('Parser.php');
include_once('Tokenizer.php');
include_once('BauplanParser.php');

/*
* Parser for Syntax Level 1
*
* Level 1 syntax is the original, default Bauplan syntax
* 
* @author: Bremen Braun
*/
class Bauplan1Parser extends Parser implements Tokenizer {
	
	public function level() {
		return 1;
	}
	
	public function tokenize($source) {
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
		
		$characters = preg_split('//', $source);
		for ($i = 0; $i < count($characters) - 1; $i++) {
			$curr = $characters[$i];
			$next = $characters[$i+1];
			
			if ($i > 0) {
				$prev = $characters[$i-1];
			}
			if ($prev != "\\") {
				###########################
				#          Types          #
				###########################
				if ($curr == '*') { # Template
					if ($next == '(') {
						if ($begin) { # For heading comment
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
						$tmpl = new Token('*(', 'T_TMPL', $line);
						
						array_push($tokens, $tmpl);
						$prev_token = $tmpl;
						$i++;
						
						$in_html = false;
					}
					else {
						$token .= $curr;
					}
				}
				elseif ($curr == '@') { # Section
					if ($next == '(') {
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
						$sctn = new Token('@(', 'T_SCTN', $line);
						
						array_push($tokens, $sctn);
						$prev_token = $sctn;
						$i++;
						
						$in_html = false;
					}
					else {
						$token .= $curr;
					}
				}
				elseif ($curr == '%') { # Code
					if ($next == '(') {
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
						$code = new Token('%(', 'T_CODE', $line);
						
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
				elseif ($curr == '$') { # Variable
					if ($next == '(') {
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
						$variable = new Token('$(', 'T_VRBL', $line);
						
						array_push($tokens, $variable);
						$prev_token = $variable;
						$i++;
						
						$in_html = false;
					}
					else {
						$token .= $curr;
					}
				}
				elseif ($curr == '!') { # Keyword
						if ($next == '(') {
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
				elseif ($curr == ')') { # End type
					if (strlen($token) > 0) {
						if (in_array($prev_token->type(), array('T_VRBL', 'T_TMPL', 'T_SCTN', 'T_CODE', 'T_KWD'))) { # support empty bodies
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
					
					$close = new Token(')', 'CLOSE', $line);
					
					array_push($tokens, $close);
					$prev_token = $close;
					$in_html = true;
				}
				
				###########################
				#        Directives       #
				###########################
				elseif ($curr == '#') { # Directive start, end
					if (strlen(trim($token)) == 0 && $prev_token->type() == 'IDENT') { # # is directive start
						$dirblock = new Token('#', 'DIR_START', $line);
						
						array_push($tokens, $dirblock);
						$prev_token = $dirblock;
						
						$in_directive = true;
					}
					elseif ($in_directive) { # # is directive end
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
						$dirblock = new Token('#', 'DIR_END', $line);
						
						array_push($tokens, $dirblock);
						$prev_token = $dirblock;
						$in_directive = false;
					}
					else {
						$token .= $curr;
					}
				}
				elseif ($curr == ':') { # Key, value separator
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
				elseif ($curr == ',') { # Directive separator
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
						$dir_sep = new Token(',', 'DIR_SEP', $line);
						
						array_push($tokens, $dir_sep);
						$prev_token = $dir_sep;
					}
					else {
						$token .= $curr;
					}
				}
				
				###########################
				#        Whitespace       #
				###########################
				elseif (in_array($curr, array(' ', "\t", "\n", "\r", "\r\n"))) {
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
				
				elseif ($curr == "\\") {
					# skip until next iteration
				}
				else {
					$token .= $curr;
				}
				
			}
			else {
				$token .= $curr;
			}
		}
		
		return $tokens;
	}
	
	public function parse($source) {
		$parser = new BauplanParser();
		return $parser->parseTokens($this->tokenize($source));
	}
}
?>