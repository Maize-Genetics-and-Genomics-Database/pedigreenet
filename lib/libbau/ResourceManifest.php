<?php
include_once('Collection.php');
include_once('Resource.php');

/*
 * Hold resouroces
 * @author: Bremen Braun
 */
class ResourceManifest implements Collection {
	private $resources;
	
	public function __construct() {
		$this->resources = array(); # hash
	}
	
	/*
	 * Add a resource only if its key is not yet in the manifest
	 */
	public function add($resource) {
		if ($this->get($resource->key()) == null) {
			$this->resources[$resource->key()] = $resource->value();
			return true;
		}
		
		return false;
	}
	
	/*
	 * Force an add to succeed no matter what, even if it means overwriting the previous value on matching key
	 */
	public function forceAdd($resource) {
		$key = $resource->key();
		if ($key != null) {
			$this->resources[$resource->key()] = $resource->value();
			return true;
		}
		
		return false;
	}
	
	public function get($key) {
		if (isset($this->resources[$key])) {
			return $this->resources[$key];
		}
		
		return null;
	}
	
	public function remove($key) {
		if (isset($this->resources[$key])) {
			$ret = $this->resources[$key];
			unset($this->resources[$key]);
			return $ret;
		}
		
		return null;
	}
	
	public function clear() {
		$this->resources = array();
	}
	
	public function merge($manifest) {
		foreach ($manifest->items() as $resource) {
			$this->add($resource);
		}
		
		return $this;
	}
	
	/*
	 * Replace values from this with those from $manifest on matching key
	 */
	public function forceMerge($manifest) {
		foreach ($manifest->items() as $resource) {
			$this->forceAdd($resource);
		}
		
		return $this;
	}
	
	public function items() {
		$resources = array();
		foreach ($this->resources as $key => $val) { # manually rebuild resources
			array_push($resources, new Resource($key, $val));
		}
		
		return $resources;
	}
}
?>
