<?php 
include_once('ResourceManifest.php');

/*
 * Do runtime statistics gathering on a typenode
 */
class StatGatherer {
	private $owner;
	private $operations; # List of operations deferred to runtime
	
	public function __construct($owner) {
		$this->owner = $owner;
		$this->operations = new ResourceManifest();
		
		# Add in 
		$this->addOperation(new Resource('runtime', new RuntimeAnalyzer($owner)));
		
	}
	
	public function enableAll() {
		foreach ($this->operations->items() as $operation) {
			$operation->value()->enable();
		}
	}
	
	public function disableAll() {
		foreach ($this->operations->items() as $operation) {
			$operation->value()->disable();
		}
	}
	
	/* TODO: Add and Remove */
	 
	public function begin() {
		foreach ($this->operations->items() as $operation) {
			$op = $operation->value();
			if ($op->isEnabled()) {
				$op->begin();
			}
		}
	}
	
	public function end() {
		foreach ($this->operations->items() as $operation) {
			$op = $operation->value();
			if ($op->isEnabled()) {
				$op->end();
			}
		}
	}
}
?>