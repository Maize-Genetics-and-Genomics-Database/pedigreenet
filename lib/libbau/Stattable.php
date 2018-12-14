<?php 

/*
 * Define a debugging operation to occur on assemble for typenode 
 */
interface Stattable {
	function enable();
	function disable();
	function isEnabled();
	
	/*
	 * Operations immediately preceding type assemble
	 */
	function begin();
	
	/*
	 * Operations immediately following type assemble
	 */
	function end();
}
?>