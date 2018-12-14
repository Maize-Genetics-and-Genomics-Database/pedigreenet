<?php
include_once('Directives.php');

/*
 * Compile-time directives for Section types
 *
 * @author: Bremen Braun
 */
class SectionDirectives extends Directives {
	private $key;

	public function executeBody($key, $val) {
		$this->key = $key;

		switch($key) {
			case 'display':
				$this->display($val);
				break;
			case 'bind':
				$this->bind($val);
				break;
			case 'loop-autoscope':
				$this->loopAutoscope($val);
				break;
      case 'visible-if-defined':
        $this->visibleIfDefined($val);
        break;
			case 'visible-unless-defined':
				$this->visibleUnlessDefined($val);
				break;

			# Aliases
			case 'disp':
				$this->display($val);
				break;

			# Unimplemented directives
			case 'memoize':
				$this->_except("Unimplemented Section Directive \"memoize\"");
				$this->memoize($val);
				break;
			case 'bind-recursive':
				$this->_except("Directive \"bind-recursive\" has not yet been implemented");
				$this->bindRecursive($val);
				break;

			default:
				$this->_except("Unrecognized directive \"$key\"");
		}
	}

	private function display($visibility) {
		if ($visibility == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}

		if ($visibility == 'off') {
			$this->caller->mute();
		}
		elseif ($visibility == 'on') {
			$this->caller->unmute();
		}
		else {
			$this->_except("Argument to \"" . $this->key . "\" must be either \"on\" or \"off\"");
		}
	}

	private function bind($identifier) {
		if ($identifier == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}

		$this->caller->bind($this->caller->_parent()->get($identifier));
	}

	private function bindRecursive($identifier) {
		if ($identifier == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}

		$this->caller->bindRecursive($this->caller->_parent()->get($identifier));
	}

	private function loopAutoscope($switch) {
		if ($switch == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}

		if ($switch == 'on') {
			$this->caller->_autoscope(true);
		}
		elseif ($switch == 'off') {
			$this->caller->_autoscope(false);
		}
		else {
			$this->_except("Directive \"" . $this->key . "\" accepts either \"on\" or \"off\"");
		}
	}

  private function visibleIfDefined($identifier) {
    if ($identifier == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $this->caller->_visibleIfDefined($identifier);
  }

	private function visibleUnlessDefined($identifier) {
		if ($identifier == null) {
			$this->_except("Directive \"" . $this->key . "\" requires an argument");
		}

		$this->caller->_visibleUnlessDefined($identifier);
	}

	private function memoize($val) {
		if ($val != null) {
			$this->_except("Directive \"" . $this->key . "\" does not  accept an argument");
		}

		$this->caller->memoize();
	}
}
?>
