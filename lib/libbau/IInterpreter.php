<?php

/*
 * Interface for defining an interpreter that Code can call to give the possibility for additional
 * Code section language support.
 * 
 * @author: Bremen Braun
 */
interface IInterpreter {
	function interpret($code);
}
?>
