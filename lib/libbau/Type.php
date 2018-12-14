<?php
include_once('IType.php');
include_once('RootedTree.php');
include_once('HTMLFormatter.php');
include_once('DirectiveManager.php');
include_once('DirectiveManifest.php');
include_once('Context.php');
include_once('ResourceManifest.php');
include_once('GlobalCollection.php');
#include_once('StatGatherer.php');

/*
* The base class from which all concrete types are derived
*
* Each Type should have a reference to the template which owns it. Each template
* defines a new scope which does not inherit symbols from any other template
* 
* TODO: Each type node should mimic the document structure as the root node does
* 
* @author: Bremen Braun
*/
abstract class Type extends RootedTree implements IType {
	private $outputFormatter;
	private $directiveManager;
	private $context;
	private $directives;	# a list of directives added to this object
	private $globals;		# shared data between each node
	private $reference;		# whether this is the original or a reference to it
	private $errors;		# whether to die with fatal error or not
	private $debugger;		# Statistics specific to this node
	protected $owner;		# the Template that holds this type
	
	public function __construct($value) {
		if ($value == 'lambda') { # allow anonymous typedefs
			$value = null;
		}
		
		parent::__construct($value);
		$this->outputFormatter  = new HTMLFormatter();
		$this->directiveManager = new DirectiveManager($this);
		$this->directives       = new DirectiveManifest();
		$this->context          = Context::PHP;
		$this->owner            = $this;
		$this->globals          = GlobalCollection::instance();
		$this->errors           = true; # fatal errors by default
#		$this->debugger         = new StatGatherer($this);
	}
	
	public function identifier() {
		return $this->_value();
	}
	
	public function upstream($identifier) {
		$cnode = $this;
		while (!$cnode->_isRoot()) {
			if ($cnode->_value() == $identifier) {
				return $cnode;
			}
			$cnode = $cnode->_parent();
		}
		
		$this->_except("No such element with identifier \"$identifier\"");
	}
	
	public function assemble() {
		$this->context = Context::TEMPLATE; # assemble could trigger runtime operations from template
		
		# Start statistical analysis
#		$this->debugger->begin();
		
		$assembled = "";
		$children = $this->_children();
		if ($children != null) {
			foreach ($children as $child) {
				if ($child->_isLeaf()) {
					$assembled .= $child->_value();
				}
				else {
					$assembled .= $child->assemble();
				}
			}
		}
		
		# End statistical analysis
#		$this->debugger->end();
		
		return $assembled;
	}

	public function publish() {
		$this->outputFormatter->streamprint($this->assemble());
	}
	
	//eksc
	public function getHTML() {
	  return $this->assemble();
	}
	
	public function _directives() {
		return $this->directives;
	}
	
	# OPTIONAL OVERRIDES
	/*
	* Allow properties to be passed upwards by this node to its parent
	*/
	public function _onAdd($parent) {
		/* Intentionally empty */
	}
	
	/*
	 * Add additional properties to the cloned object
	 */
	protected function _addProperties($clonee) {
		/* Intentionally empty */
	}
	# END OVERRIDABLE
	
	public function _reference() { #  don't want to be able to set reference to false once changed unless in this class
		$this->reference = true;
		return true;
	}
	
	public function _isReference() {
		return $this->reference;
	}
	
	public function _executeDirective($key, $value) {
		$this->directiveManager->execute($key, $value);
	}
	
	public function _context($context=null) {
		if ($context != null) {
			$this->context = $context;
		}
		
		return $this->context;
	}
	
	public function _owner($owner=null) {
		if ($owner != null) {
			$this->owner = $owner;
		}
		
		return $this->owner;
	}
	
	public function _globals() {
		return $this->globals;
	}
	
	public function _scope() {
		return $this->owner;
	}
	
	public function _file() {
		return $this->_owner()->_file();
	}
	
	/*
	 * Remove variable from symbol tree
	 */
	public function _unset() {
		$index = 0;
		$children = $this->_parent()->_children();
		foreach ($children as $child) {
			if ($child->identifier() == $this->identifier()) {
				$this->_parent()->_removeChildAtIndex($index);
				break;
			}
			
			$index++;
		}
		
		return $index;
	}
	
	public function _errors($switch=true) {
		if (isset($switch)) {
			$this->errors = $switch;
		}
		else {
			return $this->errors;
		}
	}
	
	public function _except($message="", $backtrace=true) {
		if ($this->errors) {
			$class = get_class($this);
			
			$id = $this->_value();
			if ($id == null) {
				$id = "(implicit declaration)";
			}
			else {
				$id = "\"$id\"";
			}
			
			$template = $this->owner->identifier();
			if ($template == null) {
				$template = "(lambda)";
			}
			else {
				$template = "\"$template\"";
			}
			
			$bkstr = "";
			if ($backtrace) {
				$bkstr = "<b>Backtrace:</b><br>\n" . $this->getBacktraceString();
			}
			$file = $this->_file();
			if ($file != "") {
				$file = "In file $file<br>\n";
			}
			die("<b>Bauplan Error:</b> $message<br>\n$file $bkstr");
		}
		
		return false;
	}
	
	protected function deepCopy() {
		$clone = $this->createClone();
		$clone->_parent($this->_parent());
		$clone->directiveManager = new DirectiveManager($clone);
		$clone->directives       = $this->directives;
		$clone->context          = $this->context;
		$clone->owner            = $this->owner;
		$clone->reference        = $this->reference;
##		$clone->globals          = $this->globals;
		#$clone->_addProperties($this); # specific type properties
		
		$this->cloneRecurse($clone);
		
		# re-execute directives on clone
		foreach ($clone->_directives()->items() as $directive) {
			$directive->operand($clone); # sets type to act upon
			$directive->execute();
		}
		
		return $clone;
	}
	
	protected function createClone() {
		$class = get_class($this);
		$clone = new $class($this->_value());
		
		return $clone;
	}
	
	protected function cloneRecurse($clone) {
		foreach ($this->_children() as $child) { # Clone the subtree
			if ($child->_isLeaf()) {
				$clone->_addChild($child);
			}
			else { # Child is Template, Section, Variable, or Code - have to do a recursive deep copy
				$clone->_addChild($child->deepCopy());
			}
		}
	}
        
	#####################
	#     DEBUGGING     #
	#####################
	private function getBacktraceString() {
		$backtrace = $this->getBacktrace();
		$bktrc_str = "";
		$nodecount = count($backtrace);
		
		foreach ($backtrace as $node) {
			$node_type = $node->_type();
			$node_id   = $node->identifier();
			if ($node_id == null) {
				$node_id = "(lambda)";
			}
			else {
				$node_id = "\"$node_id\"";
			}
				
			# Arrows for easier visual tracing
			$arrow = "";
			for ($i = 0; $i < $nodecount; $i++) {
				$arrow .= '-';
			}
			if (strlen($arrow) > 0) {
				$arrow .= ">";
			}
			
			$bktrc_str .= "$arrow In $node_type $node_id<br>\n";
			$nodecount--;
		}

		return $bktrc_str;
	}
        
	private function getBacktraceString_backwards() {
		$backtrace = $this->getBacktrace();
		$bktrc_str = "";
		$bktrc_ind = 0;
		foreach ($backtrace as $node) {
			$node_type = get_class($node);
			$node_id   = $node->identifier();
			if ($node_id == null) {
				$node_id = "(lambda)";
			}
			else {
				$node_id = "\"$node_id\"";
			}
			
			# Arrows for easier visual tracing
			$arrow = "";
			for ($i = 0; $i <= $bktrc_ind; $i++) {
				$arrow .= "-";
			}
			if (strlen($arrow) > 0) {
				$arrow .= ">";
			}
			
			$bktrc_str .= "$arrow In $node_type $node_id<br>\n";
			$bktrc_ind++;
		}
		
		return $bktrc_str;
	}
	
	private function getBacktrace() {
		$backtrace = array();
		
		$node = $this;
		while (!$node->_isRoot()) {
			array_push($backtrace, $node);
			$node = $node->_parent();
		}
		
		return $backtrace;
	}
}
?>
