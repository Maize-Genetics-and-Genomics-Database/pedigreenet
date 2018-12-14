<?php
include_once('Context.php');
include_once('ResourceManifest.php');

/*
 * @author: Bremen Braun
 */
abstract class Directives {
	protected $caller;
	protected $called;
	
	public function __construct($caller) {
		$this->caller   = $caller;
		$this->called   = new ResourceManifest();
		$this->executed = false;
	}
	
	public function execute($key, $val) {
		$prev_context = $this->caller->_context();
		$this->caller->_context(Context::TEMPLATE);
		$this->executeBody($key, $val);
#		$this->called->add(); # TODO: update the called list
		$this->caller->_context($prev_context);
	}
	
	protected function _except($message) {
		$type  = get_class($this->caller);
		$ident = $this->caller->identifier();
		if ($ident == null) {
			$ident = "(implicit declaration)";
		}
		$id   = "\"$ident\"";
		
		die("Bauplan Error: $message in $type $id");
	}
	
	public abstract function executeBody($key, $val);
}
?>
