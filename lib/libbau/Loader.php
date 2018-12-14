<?php
include_once('Preprocessor.php');

/*
* Template's loader class
*
* By abstracting Bauplan loads, different compiled schemes can be loaded cleanly
* The strategy for loading/saving should be seen as roughly analogous to
* Python's .pyc files
* 
* @author: Bremen Braun
*/
class Loader {
	private $parser;
	
	public function __construct() {
		$this->parser = new Preprocessor();
	}
	
	public function load($file) {
		$output = null;
		
		# try to load file directly
		if (file_exists($file)) {
			$output = $this->loadFile($file);
			
			return $output;
		}
		
		# try to load file as builtin
		$builtin = $this->asBuiltin($file);
		if (file_exists($builtin)) {
			$output = $this->loadFile($this->asBuiltin($file));
			return $output;
		}
		
		return null; # no loading succeeded
	}
	
	/*
	* Load from a string rather than a file
	*/
	public function loadSource($baustring, $resourceIdentifier, $reparse=false) {
		# Find or create the translation mapping for $resourceIdentifier
		$friendly_name = $this->getFriendlyName($resourceIdentifier);
		$rperiod       = strrpos($friendly_name, '.');
		$base          = substr($friendly_name, 0, $rperiod);
		$extension     = substr($friendly_name, $rperiod + 1);
		$compiled_file = "$base.bauc";
		
		if (!$reparse) {
			if (file_exists($compiled_file)) { # See if there's a compiled copy
				return unserialize(file_get_contents($compiled_file));
			}
		}
		
		return $this->serializeSave($this->parser->parse($baustring), $compiled_file);
	}
	
	private function loadFile($file) {
		$rperiod   = strrpos($file, '.');
		$base      = substr($file, 0, $rperiod);
		$extension = substr($file, $rperiod + 1);
		
		if ($extension != 'bauc') { # see if compiled version exists
			$compiled = "$base.bauc";
			if (file_exists($compiled)) {
				$template = unserialize(file_get_contents($compiled));
				$modified = false;
				
				# Check whether $file was modified
				if ($this->modified($file, $compiled)) {
					$this->serializeSave($this->parser->parseFile($file), $compiled);
					$modified = true;
				}
				
				# Check whether any of $file's dependencies were modified
				$dependencies = $this->findDependencies($template);
				foreach ($dependencies as $dependency) {
					$dependency_source   = $dependency->key();
					$dependency_compiled = $dependency->key() . "c"; #FIXME: should do as first few lines in method
					
					if ($this->modified($dependency_source, $dependency_compiled)) {
						$this->serializeSave($this->parser->parseFile($dependency_source), $dependency_compiled);
						$modified = true;
					}
				}
				if (!$modified) {
					return $template;
				}
			}
		}
		
		return $this->serializeSave($this->parser->parseFile($file), $compiled);
	}
	
	/*
	 * Return $resource as a name that is more suitable for a file
	 */
	private function getFriendlyName($resource) {
		$friendly = $resource;
		if (parse_url($resource)) {
			$friendly = substr($resource, strrpos($resource, '/') + 1);
		}
		
		return $friendly;
	}
	
	/*
	* Check if source file was modified since last compile
	*/
	private function modified($orig, $comp) {
		if (file_exists($orig) && file_exists($comp)) {
			if (filemtime($orig) > filemtime($comp)) {
				return true;
			}
		}
		
		return false;
	}
	
	private function findDependencies($template) {
		$dependencies = array();
		$temp_deps = $template->_dependencies();
		foreach ($temp_deps as $dependency) {
			array_push($dependencies, $dependency);
			$this->findDependencies($dependency->value()); # the value is a template object reference
		}
		
		return $dependencies;
	}
	
	/*
	* Save a serialized instance to a file
	*/
	private function serializeSave($tree, $file) {
		# If Apache doesn't own the template directory, it won't be able to save the compiled resource.
		# If this is the case, silently skip serialization
		ob_start();
	  if (($fh = fopen($file, 'w')) !== false) { # was able to open file
      $fh = fopen($file, 'w');
			fwrite($fh, serialize($tree));
			fclose($fh);
		}
		ob_end_clean();
		
		return $tree;
	}
	
	/*
	* Return $file with its path as the builtin directory
	*/
	private function asBuiltin($file) {
		$my_path = __FILE__;
		$libbau_path = substr($my_path, 0, strrpos($my_path, '/'));
		$builtin_dir = 'builtin';
		
		return "$libbau_path/$builtin_dir/$file";
	}
}
?>
