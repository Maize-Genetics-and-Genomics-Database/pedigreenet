<?php
include_once('Directives.php');

/*
 * Compile-time directives for Variables
 *
 * @author: Bremen Braun
 */
class VariableDirectives extends Directives {
  private $key;
  private $didEscape = false;

  public function executeBody($key, $val) {
    $this->key = $key;

    switch($key) {
      case 'default':
        $this->defaultValue($val);
        break;
      case 'required':
        $this->required($val);
        break;
      case 'readonly':
        $this->readonly($val);
        break;
      case 'variable':
        $this->variable($val);
        break;
      case 'get-value':
        $this->getValue($val);
        break;
      case 'escape':
        $this->escape($val);
        break;

      # Aliases
      case 'val':
        $this->defaultValue($val);
        break;
      case 'value':
        $this->defaultValue($val);
        break;
      case 'req':
        $this->required($val);
        break;
      case 'rdonly':
        $this->readonly($val);
        break;
      case 'var':
        $this->variable($val);
        break;
      case 'get-val':
        $this->getValue($val);
        break;

      # Unimplemented
      # Protected allows replace to be called only within Template context
      case 'protected':
        $this->_except("Unimplemented Variable Directive \"protected\"");
        $this->protect($val);
        break;
      case 'global':
        $this->_except("Unimplemented Variable Directive \"global\"");
        #$this->globalVariable($val);
        break;

      default:
        $this->_except("Unrecognized directive \"$key\"");
    }
  }

  private function defaultValue($value) {
    if ($value == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $this->caller->replace($value);
  }

  private function required($value) {
    if ($value != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    $this->caller->_required();
  }

  private function readonly($value) {
    if ($value != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    #TODO: readonly and protected are mutually exclusive
    $this->caller->_readonly();
  }

  private function protect($value) {
    if ($value != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    #TODO: protected and readonly are mutually exclusive
    $this->caller->_protect();
  }

  private function variable($value) {
    if ($value != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    $this->caller->_variable();
  }

  private function getValue($value) {
    if ($value == null) {
      $this->_except("Directive \"" . $this->key . "\" requires an argument");
    }

    $scope   = $this->caller->_scope(); # all the symbols in the current scope
    $symbols = $scope->_symbols();
    $symbol = $scope->get($value);
    if ($symbol == null) { # Couldn't find symbol
      $this->_except($this->key . " could not find value for Variable with identifier \"$value\"");
    }
    else { # Did find symbol
      if ($symbol->_type() != 'Variable') {
        $this->_except($this->key . " cannot get value from non-variable \"$value\"");
      }

      $this->caller->replace($symbol->value());
    }
  }

  private function escape($value) {
    if ($value != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    $this->caller->escape();
    
/* // FIXME: Clojures can't be serialized
    if (!$this->didEscape) {
      $this->caller->_registerEvent(Variable::AFTER_REPLACE, function($caller, $value) { // intercept attempt to set value and escape the set value with slashes
        $caller->_unregisterEvent(Variable::AFTER_REPLACE, 'ACTION_ESCAPE');
        $caller->replace(addslashes($value));
      }, 'ACTION_ESCAPE');

      $this->didEscape = true;
    }
*/
  }

  /*
  * If the variable is not in the global symbol table, place it there
  * else, retrieve the global symbol
  */
  private function globalVariable($value) {
    if ($value != null) {
      $this->_except("Directive \"" . $this->key . "\" does not accept an argument");
    }

    $globval = $this->caller->_globals()->get($this->caller->identifier());
    if ($globval != null) { # invoke GET behavior
      echo "USING GET BEHAVIOR IN " . $this->caller->identifier() . "<br>\n";
      $this->caller->replace($globval); #FIXME: this should replace the caller with the global reference, not just use the global value
    }
    else { # invoke SET behavior
      echo "USING SET BEHAVIOR IN " . $this->caller->identifier() . "<br>\n";
      $this->caller->_globals()->add(new Resource($this->caller->identifier(), $this->caller));
    }
  }
}
?>
