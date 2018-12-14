<?php 
include_once('StatOperation.php');

/*
 * 
 */
class RuntimeAnalyzer extends StatOperation {
	private $start_time;
	
	public function begin() {
		$this->start_time = microtime(true);
	}
	
	public function end() {
		$total = (microtime(true) - $this->start_time);
		
		echo $this->print("Total time (microtime): $total"); #FIXME: should use an ouput stream rather than just calling echo
	}
}
?>