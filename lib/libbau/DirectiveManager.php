<?php
/*
* Find directives via naming convention
* 
* @author: Bremen Braun
*/

#TODO: Remove once fixed...
include_once('CodeDirectives.php');
include_once('KeywordInstantiatorDirectives.php');
include_once('TemplateDirectives.php');
include_once('SectionDirectives.php');
include_once('VariableDirectives.php');
#END TODO

class DirectiveManager {
	private $directives;

	public function __construct($caller) {
		$module = get_class($caller) . "Directives";
		include_once($module . ".php");
		$this->directives = new $module($caller);
	}
	
	public function execute($key, $val) {
		$this->directives->execute($key, $val);
	}
}
?>
