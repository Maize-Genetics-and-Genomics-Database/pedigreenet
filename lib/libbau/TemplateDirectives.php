<?php
include_once('Directives.php');
include_once('DirectiveUnit.php');
include_once('Context.php');
include_once('Resource.php');

/*
 * Compile-time directives for Template type
 *
 * @author: Bremen Braun
 */
class TemplateDirectives extends Directives {
  private $key;

  public function executeBody($key, $val) {
    $this->key = $key;

    switch($key) {
      case 'replace':
        $this->replace($val);
        break;
      case 'load':
        $this->load($val);
        break;
      case 'load-static':
        $this->loadStatic($val);
        break;
      case 'load-remote':
        $this->loadRemote($val);
        break;
      case 'include-css':
        $this->includeCss($val);
        break;
      case 'include-js':
        $this->includeJs($val);
        break;
      case 'include-in-head':
        $this->includeInHead($val);
        break;
      case 'inherit-by-value':
        $this->inheritByVal($val); # reenable once fixed
        break;
      case 'inherit-by-reference':
        $this->inheritByRef($val);
        break;
      case 'enable-beta':
        $this->enableBeta($val);
        break;

      # Aliases
      case 'ld':
        $this->load($val);
        break;
      case 'ld-static':
        $this->loadStatic($val);
        break;
      case 'ld-remote':
        $this->loadRemote($val);
        break;
      case 'inc-css':
        $this->includeCss($val);
        break;
      case 'inc-js':
        $this->includeJs($val);
        break;
      case 'inc-in-head':
        $this->includeInHead($val);
        break;
      case 'inherit-by-val':
        $this->inheritByVal($val);
        break;
      case 'inherit-by-ref':
        $this->inheritByRef($val);
        break;

      # Unimplemented directives
      case 'enable-plugin':
        $this->_except("Unimplemented Template Directive \"enable-plugin\"");
        $this->enablePlugin($val);
        break;
      case 'load-relative':
        $this->_except("Unimplemented Template Directive \"load-relative\"");
        $this->rload($val);
        break;
      case 'load-static-relative':
        $this->_except("Unimplemented Template Directive \"load-static-relative\"");
        $this->rloadStatic($val);
        break;
      case 'include-css-relative':
        $this->_except("Unimplemented Template Directive \"include-css-relative\"");
        $this->rincludeCss($val);
        break;
      case 'include-js-relative':
        $this->_except("Unimplemented Template Directive \"include-js-relative\"");
        $this->rincludeJs($val);
        break;

      default:
        $this->_except("Unrecognized directive \"$key\"");
    }
  }

  private function replace($val) {
    if ($val != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    $this->caller->_replace(true);
  }

  private function load($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\"  requires an argument");
    }

    $this->caller->load($path);
  }

  private function loadStatic($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $this->caller->loadStatic($path);
  }

  private function loadRemote($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    #$path = 'http://' . $path; # colons will mess up the directives and it's easier to fix here
    $this->caller->loadRemote($path);
  }

  private function enablePlugin($plugin) {
    if ($plugin == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    #TODO
  }

  private function rload($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    # We need the parent to execute this directive since this will attempt to be executed before the parent's path is set.
    # This, like most things, should change in the future with Bauplan3 but will be backwards compatible. In the spirit of this version, let's just hack it on and pretend
    # that time/motivation will be available later to do this properly
    $this->caller->_owner()->_directives()->add(new DirectiveUnit('loadrel-helper', $path, $this->caller->_owner())); # Defer the operation by manually calling it later
  }

  private function rloadStatic($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    #TODO
    #$this->caller->loadStatic($abs_path);
  }

  private function includeCss($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    if ($this->caller->_globals()->get('remote')) { # remote call, may need to mangle $path to point to server base
      if (!preg_match('/^https?:/', $path)) { # full path given; don't mangle
        $path = $this->caller->_globals()->get('remote-base') . $path;
      }
    }
    $resource = new Resource($path, "<link rel='stylesheet' type='text/css' href='$path'>");
    $this->caller->_resourceManifest()->add($resource);
  }

  private function includeJs($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    if ($this->caller->_globals()->get('remote')) { # remote call, may need to mangle $path to point to server base
      if (!preg_match('/^http|https\:\/\//', $path)) { # full path given; don't mangle
        $path = $this->caller->_globals()->get('remote-base') . $path;
      }
    }
    $resource = new Resource($path, "<script type='text/javascript' src='$path'></script>");
    $this->caller->_resourceManifest()->add($resource);
  }

  private function includeInHead($string) {
    if ($string == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $resource = new Resource($string, $string);
    $this->caller->_resourceManifest()->add($resource);
  }

  private function rincludeCss($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    #TODO
    $location = $this->caller->_fsLocation();
#		$abs_path = (SOMETHING) . $path;
#		$this->includeCss($abs_path);
  }

  private function rincludeJs($path) {
    if ($path == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    #TODO: location isn't set until it's loaded which is directly following compilation...
    $location = $this->caller->_fsLocation();
#		$abs_path = (SOMETHING) . $path;
#		$this->includeJs($abs_path);
  }

  /*
   * Pass down variable by value
   *
   * Takes optional argument $val which is the variable from the parent scope to inherit
   * If $val is not specified, all are passed down
   */
  private function inheritByVal($val) {
    if ($val == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $this->inherit($val, false);
  }

  /*
   * Pass down variable by reference
   */
  private function inheritByRef($val) {
    if ($val == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $this->inherit($val, true);
  }

  private function inherit($val, $reference) {
    $o_sym   = $this->caller->_owner()->_symbols();
    $symbols = $o_sym->items(); # by default, pass all
    if ($val != null) { # pass specific symbol
      $symbols = array(new Resource($val, $o_sym->get($val)));
    }

    $error_reporting = $this->caller->_errors();
    $this->caller->_errors(false); # turn off errors because we won't be able to get lambda identifiers but they may be in the symtable

    foreach ($symbols as $symbol) {
      if (($type = $this->caller->get($symbol->key()))) {
        if ($type->_type() == 'Variable') { # no sane action for types other than Variable, but there is probably a cleaner way to do this
          if ($reference) { # pass by reference
            $replacement = $symbol->value();
            $target_node = $type->_parent();
            $type_index  = $type->_unset(); # remove the old reference

            $target_node->_addChildAtIndex($replacement, $type_index); # make a reference
          }
          else { # pass by value
            $this->caller->_symbols()->forceAdd($symbol);
            $type->replace($symbol->value()->value());
          }
        }
        else {
          $this->_except('Attempt to pass down non-variable "' . $type->identifier() . '" of type ' . $type->_type());
        }
      }
    }

    $this->caller->_errors($error_reporting); # restore error reporting to previous state
  }

  private function enableBeta($val) {
    if ($val != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    $this->caller->_allowBeta();
  }
}
?>
