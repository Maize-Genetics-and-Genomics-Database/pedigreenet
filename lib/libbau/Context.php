<?php
/*
* An enum for setting the calling context of a function. Behavior can vary depending on context.
*
* An enum is one of the following:
* - PHP: Called explicitly in the logic code
* - Template: Called by a template directive
* - Code: Called by a template Code section
* 
* @author: Bremen Braun
*/
class Context {
	const PHP      = 0;
	const TEMPLATE = 1;
	const CODE     = 2;
	
	static function contextName($enum) {
		if ($enum == 0) {
			return "PHP";
		}
		else if ($enum == 1) {
			return "TEMPLATE";
		}
		else if ($enum == 2) {
			return "CODE";
		}
	}
}
?>
