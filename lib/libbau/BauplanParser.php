<?php
include_once('Parser.php');
include_once('DirectiveUnit.php');
include_once('Template.php');
include_once('Section.php');
include_once('Code.php');
include_once('Variable.php');
include_once('KeywordInstantiator.php');
include_once('HTML.php');

/*
 * Create an object tree from a token stream
 * 
 * @author: Bremen Braun
 */
class BauplanParser extends Parser {

	public function parse($source) {
		$tokens = $this->tokenize($source);
		return $this->parseTokens($tokens);
	}
	
	public function parseTokens($tokens) {
		$tree      = new RootedTree('LAMBDA');
		$iter      = new TokenIterator($tokens);
		$prevtoken = null;
		$curnode   = null;
		
		$dir_key = "";
		$dir_val = "";
		
		# States
		$in_typedef         = false;
		$in_directive_block = false;
		
		while ($iter->hasNextToken()) {
			$token = $iter->nextToken();
			$this->token = $token;
			
			if ($curnode == null) { # make sure first node is a template
				if ($token->type() != 'T_TMPL') {
					$this->error("Expected T_TMPL, saw \"" . $token->symbol() . "\" of type " . $token->type());
				}
				
				$ident = $iter->nextToken();
				if ($ident->type() != 'IDENT') {
					$this->error("Expected IDENT, saw \"" . $ident->symbol() . "\" of type " . $ident->type());
				}
				
				$curnode = $tree->_addChild(new Template($ident->symbol()));
			}
			else {
				switch($token->type()) {
					case 'T_TMPL':
						$in_typedef = true;
						
						$ident = $iter->nextToken();
						if ($ident->type() != 'IDENT') {
							$this->error("Expected IDENT, saw \"" . $ident->symbol() . "\" of type " . $ident->type());
						}
					
						$curnode = $curnode->_addChild(new Template($ident->symbol()));
						break;
					case 'T_SCTN':
						$in_typedef = true;
						
						$ident = $iter->nextToken();
						if ($ident->type() != 'IDENT') {
							$this->error("Expected IDENT, saw \"" . $ident->symbol() . "\" of type " . $ident->type());
						}
						
						$curnode = $curnode->_addChild(new Section($ident->symbol()));
						break;
					case 'T_CODE':
						$in_typedef = true;
						
						$ident = $iter->nextToken();
						if ($ident->type() != 'IDENT') {
							$this->error("Expected IDENT, saw \"" . $ident->symbol() . "\" of type " . $ident->type());
						}
						
						$curnode = $curnode->_addChild(new Code($ident->symbol()));
						break;
					case 'T_VRBL':
						$in_typedef = true;
						
						$ident = $iter->nextToken();
						if ($ident->type() != 'IDENT') {
							$this->error("Expected IDENT, saw \"" . $ident->symbol() . "\" of type " . $ident->type());
						}
						
						$curnode = $curnode->_addChild(new Variable($ident->symbol()));
						break;
					case 'T_KWD':
						$in_typedef = true;
						
						$ident = $iter->nextToken();
						if ($ident->type() != 'IDENT') {
							$this->error("Expected IDENT, saw \"" . $ident->symbol() . "\" of type " . $ident->type());
						}
						
						$curnode = $curnode->_addChild(new KeywordInstantiator($ident->symbol()));
						break;
					case 'CLOSE':
						$in_typedef = false;
						if ($curnode->_isRoot()) {
							$this->error("Unexpected token \")\" (already in shallowest scope)");
						}
						
						# Execute directives as a postcondition
						if (method_exists($curnode, '_directives')) { # this could be the root which is a RootedTree
							foreach ($curnode->_directives()->items() as $directive) {
								if (!$directive->executed()) {
									$directive->execute();
								}
							}
						}
						
						$curnode = $curnode->_parent();
						break;
					case 'IDENT':
						$this->error("Unexpected IDENT token " . $token->symbol());
					case 'DIR_START':
						$in_directive_block = true;
						break;
					case 'DIR_KEY':
						$dir_key = $token->symbol();
						break;
					case 'DIR_VAL':
						$dir_val = $token->symbol();
						break;
					case 'DIR_SEP': # evaluate built directive
						if (($prevtoken->type() != 'DIR_KEY' && $prevtoken->type() != 'DIR_VAL') || $dir_key == null) {
							$this->error("Unexpected DIR_SEP token");
						}
						
						$curnode->_directives()->add(new DirectiveUnit($dir_key, $dir_val, $curnode));
						$dir_key = "";
						$dir_val = "";
						break;
					case 'DIR_END':
						$in_directive_block = false;
						
						if (($prevtoken->type() != 'DIR_KEY' && $prevtoken->type() != 'DIR_VAL') || $dir_key == null) {
							$this->error("Unexpected DIR_SEP token");
						}
						
						$curnode->_directives()->add(new DirectiveUnit($dir_key, $dir_val, $curnode));
						$dir_key = "";
						$dir_val = "";
						break;
					case 'HTML':
						$symbol = $token->symbol();
						if (in_array($prevtoken->type(), array('T_TMPL', 'T_SCTN', 'T_CODE', 'T_VRBL', 'T_KWD')) || $iter->peekNextToken()->type() == 'CLOSE') {
							if (trim($symbol) != "") {
								$curnode->_addChild(new HTML($symbol));
							}
						}
						else {
							$curnode->_addChild(new HTML($symbol));
						}
						break;
					case 'CODE':
						$curnode->_code($token->symbol());
						break;
					default:
						$this->error('Unrecognized token "' . $token->symbol() . '"');
				}
			}
			
			$prevtoken = $token;
		}
		
		if ($curnode != $tree) {
			if ($curnode == null) {
				$this->error("No template given");
			}
			$this->error("Missing closing parenthesis (exited in scope \"" . $curnode->identifier() . "\")");
		}
		
		return $tree->_getChild(0);
	}
}
?>
