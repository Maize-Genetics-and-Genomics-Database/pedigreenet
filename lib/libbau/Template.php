<?php
include_once('Nary.php');
include_once('Loader.php');
include_once('SymbolTable.php');
include_once('DependencyCollection.php');
include_once('ResourceManifest.php');

/*
 * The Template type
 * 
 * @author: Bremen Braun
 */
class Template extends Nary {
	private $loader;
	private $resourceManifest;
	private $templateDependencies;
	private $file; # template can be file
	private $symbols; # template-scoped types
	private $replace;
	private $allowBeta;
	
	public function __construct($value) {
		parent::__construct($value);
		$this->allowBeta            = false; # beta features are disabled by default
		$this->replace              = false; # default action for loading is to add to
		$this->loader               = new Loader();
		$this->templateDependencies = new ResourceManifest(); # holds templates this template is dependent upon
		$this->resourceManifest     = DependencyCollection::instance(); # build dependencies at compile time and include them at runtime
		
		# Set by load or loadRemote
		$this->file = null;
		
		# Template-scoping requires a symbol table per template
		$this->symbols = new SymbolTable();
	}
	
	/*
	* Load a template resource
	*
	* If the first template in the call was loaded remotely, each template directive call to load should remotely load the dependencies.
	* This will allow templates to act the same whether they're located locally or remotely 
	*/
	public function load($resource) {
		$template;
		if ($this->_context() == Context::PHP || !$this->_globals()->get('remote')) { # even if there have been remote requests, caller knows what they want
			$template = $this->localLoad($resource);
		}
		else { # Triggered based on context; get fully-qualified resource name
			$resource = $this->_globals()->get('remote-base') . $resource;
			$template = $this->remoteLoad($resource);
		}
		
		$this->_owner()->templateDependencies->add(new Resource($resource, $template));
		return $template;
	}
	
	public function set($template) {
		return $this->_addChild($template);
	}
	
	public function replace($template) {
		$template->_onAdd($this); # Need to manually call this event since no children are being added
		$this->_value($template->_value());
		$this->_children($template->_children());
		return $this;
	}
	
	public function loadStatic($file) {
		if (!file_exists($file)) {
			$this->_except("No such file $file");
		}
		
		$contents = file_get_contents($file);
		return $this->setStatic($contents);
	}
	
	public function loadRemote($url) {
		$this->_globals()->add(new Resource('remote', true));
		$url_parts = parse_url($url);
		$baseURL   = 'https://' . $url_parts['host'] . '/';
			
		# Child will be root until compilation phase ends so it will need to find the baseURL attribute in globals
		$this->_globals()->add(new Resource('remote-base', $baseURL));
		
    $template = $this->remoteLoad($url);
		$this->_globals()->set('remote', false); # end the deluge
		return $template;
	}
	
	public function setStatic($html) {
		$contents = new HTML($html);
		
		$this->_addChild($contents);
		return $contents;
	}
	
	public function _type() {
		return 'Template';
	}
	
	public function _resourceManifest() {
		return $this->resourceManifest;
	}
	
	public function _symbols() {
		return $this->symbols;
	}
	
	public function _file() {
		return $this->file;
	}
	
	public function _dependencies() {
		return $this->templateDependencies->items();
	}
	
	public function _allowBeta($switch=true) {
		$this->allowBeta = $switch;
	}
	
	public function _betaEnabled() {
		return $this->allowBeta;
	}
	
	public function _replace($switch=true) {
		if ($switch != null) {
			$this->replace = $switch;
		}
		
		return $this->replace;
	}
	
	#OVERRIDES
	/*
	* Since this defines a new symbol table, there's no need to consult
	* the owner
	*/
	public function _addChild($tree) {
		# muck around with owner so that the right symbol table is found (this)
		$old_owner = $this->_owner();
		$this->owner = $this;
		$return = parent::_addChild($tree);
		$this->owner = $old_owner;
		
		return $return;
	}
	
	/*
	 * Because scope is rooted at template, return this rather than the owner
	 */
	public function _scope() {
		return $this;
	}
	#END OVERRIDES
	
	private function localLoad($file) {
		$template = $this->loader->load($file);
		if ($template == null) {
			$this->_except("No such file $file");
		}
		
		# Set the path to the resource to enable relative loads
		# TODO: Check all @INC
		$template->file = $file;
		
		#FIXME: added to help fix rooting problem
		$template->_root($this->_root());
		#ENDFIXME
		
		if ($this->replace) {
			return $this->replace($template);
		}
		
		return $this->set($template);
	}
	
	private function remoteLoad($url) {
		$template = file_get_contents($url);
		if (!$template) { # bad URL, etc
			$this->_except("Couldn't locate remote resource $url");
		}
		
		#TODO: set remote to true in globals
		$template = $this->loader->loadSource($template, $url);
		$template->file = "(Remote resource: $url)";
		
		#FIXME: added to help fix rooting problem
		$template->_root($this->_root());
		#ENDFIXME
		
		if ($this->replace) {
			return $this->replace($template);
		}
		
		return $this->set($template);
	}
	
	protected function _addProperties($clonee) {
		// nothing to do
	}
}
?>
