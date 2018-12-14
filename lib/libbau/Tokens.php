<?php
/*
* Bauplan token enum
* 
* @author: Bremen Braun
*/
class Tokens {
	const T_TEMPLATE_SIGIL             = 0;
	const T_SECTION_SIGIL              = 1;
	const T_VARIABLE_SIGIL             = 2;
	const T_CODE_SIGIL                 = 3;
	const T_TYPE_START                 = 4;
	const T_TYPE_END                   = 5;
	const T_DIRECTIVE_START            = 6;
	const T_DIRECTIVE_END              = 7;
	const T_DIRECTIVE_SEPARATOR        = 8;
	const T_DIRECTIVE_KEYVAL_SEPARATOR = 9;
	const T_LITERAL                    = 10;
	const T_NEWLINE                    = 11;
	
	public static function getTokenType($enumval) {
		switch($enumval) {
			case 0:
				return 'T_TEMPLATE_SIGIL';
				break;
			case 1:
				return 'T_SECTION_SIGIL';
				break;
			case 2:
				return 'T_VARIABLE_SIGIL';
				break;
			case 3:
				return 'T_CODE_SIGIL';
				break;
			case 4:
				return 'T_TYPE_START';
				break;
			case 5:
				return 'T_TYPE_END';
				break;
			case 6:
				return 'T_DIRECTIVE_START';
				break;
			case 7:
				return 'T_DIRECTIVE_END';
				break;
			case 8:
				return 'T_DIRECTIVE_SEPARATOR';
				break;
			case 9:
				return 'T_DIRECTIVE_KEYVAL_SEPARATOR';
				break;
			case 10:
				return 'T_LITERAL';
				break;
			case 11:
				return 'T_NEWLINE':
				break;
			default:
				throw new Exception('Bad ENUMVAL for token');
		}
	}
}
?>
