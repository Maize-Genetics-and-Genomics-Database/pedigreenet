<?php
include_once('Nary.php');
include_once('Template.php'); # For autoscoping

/*
 * Class for Section type
 *
 * @author: Bremen Braun
 */
class Section extends Nary {
	private $visible;		# whether or not this template and its contents will display on assemble
	private $autoscoped;	# automatically create enclosing lambda scope on loops
	private $bindings;		# sections whose displays act in unison
	private $count;			# times looped through
	private $visibilityDependencies;

	public function __construct($value, $visible=true) {
		parent::__construct($value);
		$this->owner      = null;
		$this->visible    = $visible;
		$this->bindings   = array();
		$this->autoscoped = true;
		$this->count      = 1;
    $this->visibilityDependencies = array();
		$this->inverseVisibilityDependencies = array(); // map of forward/inverse dependencies
	}

	public function bind($section) {
		array_push($this->bindings, $section);

		return $this;
	}

	public function mute() {
		$this->visible = false;

		/*
		if ($this->visible) { # If calling mute actually invokes a change in state, continue chain to all bound sections
			$this->visible = false;
			foreach ($this->bindings->bindings() as $binding) {
				$binding->toggle();
			}
		}
		*/

		return $this;
	}

	public function unmute() {
		$this->visible = true;

		/*
		if (!$this->visible) { # If calling unmute actually invokes a change in state, continue chain to all bound sections
			$this->visible = true;

			foreach ($this->bindings->bindings() as $binding) {
				$binding->toggle();
			}
		}
		*/

		return $this;
	}

	public function toggle() {
		if ($this->visible == false) {
			$this->visible = true;
		}
		else {
			$this->visible = false;
		}

		foreach ($this->bindings as $binding) {
			if ($binding->visible == false) {
				$binding->visible = true;
			}
			else {
				$binding->visible = false;
			}
		}

		return $this;
	}

	public function unroll($identifier, $array) {
		if ($this->autoscoped) {
			$this->doAutoscoping();
		}

		$incr_build = array();
		foreach ($array as $value) {
			$clone = $this->deepCopy();
			/*
			if ($this->autoscoped) {
				$autoscope = new Template('lambda');
				$clone = $autoscope->_addChild($clone);
			}
			*/
			$clone->get($identifier)->replace($value);
			array_push($incr_build, $clone);
		}

		if (count($incr_build) > 0) {
			$this->_children(array_shift($incr_build)->_children());
			foreach ($incr_build as $section) {
				foreach ($section->_children() as $child) {
					$this->_addChild($child);
				}

				$this->count++;
			}
		}

		return $this;
	}

	public function loop($kv_array) {
		if ($this->autoscoped) {
			$this->doAutoscoping();
		}

		$incr_build = array();
		while (count($kv_array) > 0) {
			$clone = $this->deepCopy();
			foreach (array_shift($kv_array) as $key => $value) {
				/*
				if ($this->autoscoped) {
					$autoscope = new Template('lambda');
					$clone = $autoscope->_addChild($clone);
				}
				*/

				$node = $clone->get($key);
        if ($node->_type() == 'Variable') {
          $node->replace($value);
        }
        else if ($node->_type() == 'Section') { // recurse
          $node->loop($value);
        }
			}

			array_push($incr_build, $clone);
		}

		# Totally rebuild $this
		return $this->rebuildFromArray($incr_build);
	}

	# Just leaving this here for backwards compatibility...
	public function loop_array($kv_array) {
		return $this->loop($kv_array);
	}

	/*
	* Clone the section $iterations times
	*/
	public function copyLoop($iterations) {
		if ($iterations < 0) {
			$this->_except("Argument to copyLoop must be a positive number");
		}

		if ($this->autoscoped) {
			$this->doAutoscoping();
		}

		$incr_build = array();
		for ($i = 0; $i < $iterations; $i++) {
			$clone = $this->deepCopy();
			/*
			if ($this->autoscoped) {
				$autoscope = new Template('lambda');
				$clone = $autoscope->_addChild($clone);
			}
			*/

			array_push($incr_build, $clone);
		}

		# Totally rebuild $this
		return $this->rebuildFromArray($incr_build);
	}

	public function count() {
		return $this->count;
	}

	public function assemble() {
    /*
     * Make sure all variable dependencies are defined before displaying.
     * This can't be done in the directive because directives only handle compile-time stuff due
     * to the fact I designed this poorly way back when
     */
    $scope = $this->_scope();
    foreach ($this->visibilityDependencies as $identifier => $type) {
      $val = null;
      if ($identifier && $scope->has($identifier)) {
        $var = $scope->get($identifier);
        if ($var->_type() !== 'Variable') {
          $this->_except("Visibility dependencies must be variables. Type is " . $var->_type() . " for identifier $identifier");
        }
        $val = $scope->get($identifier)->value();
      }
      if (!$val) { // do opposite in if/else for toggle below
				$type == 'forward' ? $this->unmute() : $this->mute();
      }
			else {
				$type == 'forward' ? $this->mute() : $this->unmute();
			}
			$this->toggle(); // need to toggle in order to trigger bindings
    }

		if ($this->visible) {
			return parent::assemble();
		}
	}

	/*
	* Flag for automatically adding new lambda scope to the section on each loop
	*/
	public function _autoscope($switch) {
		$this->autoscoped = $switch;
	}

	public function _type() {
		return 'Section';
	}

  public function _visibleIfDefined($identifier) {
		$this->visibilityDependencies[$identifier] = 'forward';
  }

	public function _visibleUnlessDefined($identifier) {
		$this->visibilityDependencies[$identifier] = 'inverse';
	}

	protected function _addProperties($clonee) {
		$this->visible    = $clonee->visible;
		$this->bindings   = $clonee->bindings;
		$this->autoscoped = $clonee->autoscoped;
	}

	private function rebuildFromArray($array) {
		$ret_array = array();
		if (count($array) > 0) {
			$this->_children(array_shift($array)->_children());
			foreach ($array as $section) {
				array_push($ret_array, $section);
				foreach ($section->_children() as $child) {
					$this->_addChild($child);
				}
			}
		}

		$this->count = count($ret_array) + 1;
		array_unshift($ret_array, $this);
		return $ret_array;
	}

	private function doAutoscoping() {
		$lambda = new Template('lambda'); # the implicit scope
		foreach ($this->_children() as $child) {
			$lambda->_addChild($child);
		}

		$this->_children(array($lambda));
	}
}
?>
