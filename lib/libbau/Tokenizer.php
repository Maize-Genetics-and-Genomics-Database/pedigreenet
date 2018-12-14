<?php 

interface Tokenizer {
	/*
	 * Return an array of tokens from source
	 */
	function tokenize($source);
}
?>