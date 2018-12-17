# Bauplan 

Bauplan, developed by Bremen Braun, is a custom templating language that PedigreeNet was built with. Just including these libraries with the PedigreeNet code will be enough to get the tool working in your installation.

Users who wish to edit or expand upon the tool should become familiar with the basic syntax of Bauplan. First, in the controller files (PHP) the bauplan object is created with :

  $bauplan = new Bauplan('Welcome to MaizeGDB');

The .bau files are bauplan templates that are mostly HTML, but do contain some bauplan syntax. These files are loaded into the bauplan object with this command:
 
  $tmpl = 'templates/tools/breeders_toolbox.bau';
  $mgdb = $bauplan->template($bauplan->template()->load($tmpl));
  
From here on the $mgdb variable can control the HTML in the .bau file specified in $tmpl. The full documentation of how to use Bauplan is given below.

## Type System
Definable types are classifiable into 3 different supertypes:

N-ary: Able to hold other types
  Template
  Section
Unary: Cannot hold other types
  Variable
  Code
N1ary: Can only hold Unary types
  Keyword

### Template
The outer-most type for every template file. Templates may be thought of as being analogous to objects in OOP and therefore it will be useful to structure your page with each distinct page element being created as a template.

### Section
Provides a grouping for types and text. You may wish to use a section for any dynamic data that follows a repetitive format (a loop) or for data whose display is conditional.

### Variable
A variable is as in any other language. Use these as placeholders for values which will get filled in in the logical portion (that is, the PHP file that corresponds to the template).

### Keyword
A keyword is a special type that is more like a function. It is not named but instead uses the name field as the function to call. These are functions specifically for text formatting and display and will be described in detail below.

### Code
Although the Code section provides logical code in full, as well as access to the Bauplan Object Tree by referencing $this, Code within templates should ONLY be used for formatting logic and not for any business logic. For example, inside of a template which displays links to other pages on the server as well as the current page, a Code section may be used to underline the link which refers to the current page. Or, on a page that uses loops for displaying rows in a table, a Code section may be used to alternate row colors.

IMPORTANT: Since templates may be served remotely, .htaccess won't be written to restrict viewing of .bau files which means ANY PHP CODE IN TEMPLATES WILL BE VISIBLE AS SOURCE. Because of this, make sure all code that enters Code sections is secure and does not contain any passwords!

### Generic (HTML)
This type is created implicitly and holds markup text. Because Bauplan uses a context-sensitive grammar, there is no manual declaration of the Generic type. There is nothing special about the Generic type, meaning Bauplan doesn't have special operations for dealing with DOM elements.

## Syntax
Due to the use of a preprocessor, Bauplan syntax is available in (at least) 3 levels.

*IMPORTANT NOTE:* Because all levels of syntax are transformed into the same structure for the object tree, it's perfectly fine to have multiple template files written with different syntax levels contained within the same project. Syntax is only relevant at the per-file level.

### Level 1 (deprecated)
  Type Sigils
Template: *
Section: @
Variable: $
Keyword: !
Code: %
Directive section start: #
Directive section end: #
Directive separator: ,


### Level 2
Type Sigils
Template: *
Section: @
Variable: $
Keyword: #
Code: &
Directive section start: {
Directive section end: }
Directive separator: |
Comments: Started with ;; and ended by newline

### Level 3
(Same as level 2 by default but allows defining your own symbols - described in Preprocessor section below)

Type Sigils
Template: *
Section: @
Variable: $
Keyword: #
Code: &
Directive section start: {
Directive section end: }
Directive separator: |
Comments: Started with ;; and ended by newline

### Escape Character
For all levels, the character \ is the escape character. Due to the context-sensitive nature of Bauplan in order to ease user responsibility, certain characters are only tokens in combination with a special designator token. For instance, the “type open” token, “(”, is “type open” when it is preceded by a type designator token (“*”, “@”, “$”, “&” for syntax level 2) and functions as part of a generic type string otherwise. Symbols which need to be escaped are:

*(
@(
$(
!( (or #( in syntax levels 2 and 3)
%( (or &( in syntax levels 2 and 3)
)
Within directive sections:
# ({ and } in syntax levels 2 and 3)
:
, (| in syntax levels 2 and 3)
NOTE: Inside of Code sections, PHP escapes will have to be double-escaped.

## Identifiers
A valid Bauplan identifier is any combination of symbols that does not contain a space. As best practice, do not use camelCase or underscores to separate words in a multiword identifier; instead, use a hyphen (-). In addition to these, there is one special identifier keyword named lambda.

### Lambda Identifier
Any type named “lambda” will be created as an anonymous type. That is, it cannot be gotten with PHP code using get() and cannot be found using has() and will not be placed into the symbol table. Because of this, it's a good idea to use lambda when defining sections that you don't want the user (the user here is assumed to be the one operating on the template through PHP code) to be able to manipulate or redefine; for example, a hidden section declared for variable initialization, or for defining a new scope. Lambda identifiers are available at all syntax levels because it's a language feature, not a syntax feature.

Examples of uses for lambda identifiers

$$SYNTAX-LEVEL 2
*(template
   @(lambda ;; this section is for variable initialization
      $(name {default: Bremen Braun})
   )
   
   *(lambda ;; using a section just for scoping; don't have a good reason to name it
      ...
   )
)

## Keywords
Keywords are like native functions. They are called at runtime, unlike directives which are called during compilation. The syntax for calling a keyword is similar to that of creating a type, except that instead of providing an identifier, the identifier is the name of the function to call. Keywords can be nested and are evaluated inside-out, as you would expect a composite function to act. There is no generic “if” statement, but there is an assortment of positive and negative conditionals which act as specific ifs and if-nots. The else portion of the if statement is provided via the special keyword “alt”, which stands for “alternative”.

Example:

$$SYNTAX-LEVEL 2
*(image
   ;; wrap an HTML image entity as a template
   <img src="$(source {required})" #(unless-empty alt="$(alt)") #(unless-empty name="$(name)") #(unless-empty id="$(id)") #(unless-empty class="$(class)")/>
)
Another example, showing nested keywords and if-else statements:

$$SYNTAX-LEVEL 2
*(example
   @(lambda {display: off} ;; inits
      $(var1 {default: one})
      $(var2 {default: two})
   )
   
   ;; nested keywords, demonstrating evaluation order
   #(upper-case #(lower-case testing output of nested functions)) ;; outputs "TESTING OUTPUT OF NESTED FUNCTIONS" because the inner function is evaluated before the outer
   
   ;; if statements
   #(if-variable-equals {argument: var1 | argument: one} ;; first argument is ID of variable, second is value to test against. This statement evaluates to true
      #(if-variable-equals {argument: var2 | argument: three} ;; evaluates to false
         var1 equals "one" and var2 equals "three"
      )
      #(alt
         var1 equals "one" but var2 does not equal "three" ;; this one is output
      )
   )
   #(alt
      var1 does not equal "one"
   )
)
## Implemented Keywords
The following is a full list of implemented keywords

# upper-case
Takes all HTML within keyword and converts it to upper case

Does not accept an argument

# lower-case
Takes all HTML within keyword and converts it to lower case

Does not accept an argument

# increment-int
Takes 1 or more argument where the first argument is the ID of the variable whose value to increment and the optional second argument is the step for increment (default: 1)

# decrement-int
Takes 1 or more argument where the first argument is the ID of the variable whose value to decrement and the optional second argument is the step for decrement (default: 1)

# unless-empty
Checks all variables within the keyword (that is, between the opening and closing parentheses) and does not display anything within the keyword if any variable is found to be undefined

Accepts arguments, which are the identifiers of the variables to check
If no argument is given, everything contained in keyword is checked for an empty child
Conditional Keyword

# if-empty
Checks variable given in argument and, if empty, displays. If the variable has a value, nothing inside of the keyword will be assembled.

Requires at least one argument, which is the identifier of the variable to check
Can be used for muting/unmuting sections within a template
Conditional Keyword

# if-equal
Takes 2 or more arguments which are variable identifiers and displays everything inside of the keyword if all argument values are equal

Requires at least 2 arguments
Conditional Keyword

# unless-equal
Opposite of if-equal; takes 2 or more arguments which are variable identifiers and displays everything inside of the keyword if at least one argument value is different from the others

Requires at least 2 arguments
Conditional Keyword

# if-exists
Checks for the existence of a variable within the current scope. If the variable exists, even if it is empty, the keyword body is executed

Requires at least 1 argument
Conditional Keyword

# unless-exists
Opposite of if-exists

Requires at least 1 argument
Conditional Keyword

# if-variable-equals
Takes two arguments in order which are 1) Variable ID and 2) the value to test against. If the variable's value and the test value are equal, the keyword body is executed

Requires exactly 2 arguments
Conditional Keyword

# unless-variable-equals
Opposite of if-variable-equals

Requires exactly 2 arguments
Conditional Keyword

# alt
Alternative to be evaluated if conditional keyword preceding it evaluates to false. Unlike with traditional languages, the alternative does not have to directly follow the condition for which it is an alternative.

Does not accept an argument
Cannot stand alone; must follow (although not necessarily immediately) conditional keyword
Not scoped; previous conditional keyword will be closest previous conditional called no matter what scope it's found in
Beta Feature

# test-cond
General purpose if-statement like keyword which tests a conditional

Does not accept an argument
Conditional Keyword
Beta Feature

### Scope Rules
All Bauplan types are template-scoped which means everything inside of a template, other than nested templates, share the same scope (unless manually stated with the “inherit-scope” directive).

## Example
$$SYNTAX-LEVEL 2
*(scope-test
   @(lambda {display: off} ;; inits
      $(var1 { readonly | default: VAR1 })
   )
   
   In outer scope, \$(var1\) is: $(var1) ;; prints VAR1
   
   *(lambda ;; the inner scope
      In inner scope, \$(var1\) is: $(var1) ;; prints nothing because $(var1) is not defined in this scope
      $(var1 {default: INNER VAR1}) ;; prints "INNER VAR1" because it's being defined here
   )
   
   ;; back in outer scope
   Back in outer scope, \$(var1\) is: $(var1) ;; prints VAR1
)

### Directives
Directives are compile-time evaluations for types.

## Template
enable-beta
Allow use of beta features
Thus far, beta features are restricted to certain keywords and it is likely it will remain this way

load: <path/to/template.bau>
loads a template into the current template
NOTE: Path is relative to the PHP file which created the main Bauplan object

load-static: <path/to/file.html>
loads a file into the current template without parsing it
NOTE: Path is relative to the PHP file which created the main Bauplan object

load-remote: <URL for template located remotely>
loads a template from a remote server into the current template
replace
When a template is loaded, set its behavior to replace the currently loaded template
Can be used in conjunction with “load” or “load-*” to establish a default template to load

include-js: <path/to/file.js>
Declares <path/to/file.js> as a template dependency and requires it in the document head before publishing
NOTE: Path is relative to the PHP file which created the main Bauplan object

include-css: <path/to/file.css>
Declares <path/to/file.css> as a template dependency and requires it in the document head before publishing
NOTE: Path is relative to the PHP file which created the main Bauplan object

include-in-head: <text-to-include>
Includes <text-to-include> in the document head. Useful for adding dependencies which are not js or css (such as link rels for favicons)

inherit-by-value: <OPTIONAL: variable ID to inherit from parent>
Passes all symbols from parent scope by value to current scope
If optional argument is not given, all symbols from parent scope are inherited by value
Pass-down happens at compile time, so any change to the variable from the parent scope will not be reflected in the current scope since this is pass by value

inherit-by-reference: <OPTIONAL: variable ID to inherit from parent>
Passes all symbols from parent scope by reference to current scope
If optional argument is not given, all symbols from parent scope are inherited by reference
Since references are shared, a change to one reference will be reflected by all

ld: <path/to/template.bau>
Alias for “load”

ld-static: <path/to/file.html>
Alias for “load-static”

ld-remote: <URL for template located remotely>
Alias for “load-remote”

inc-css: <path/to/file.css>
Alias for “include-css”

inc-js: <path/to/file.js>
Alias for “include-js”

inc-in-head: <text-to-include>
Alias for “include-in-head”

inherit-by-val: <OPTIONAL: variable ID to inherit from parent>
Alias for “inherit-by-value”

inherit-by-ref: <OPTIONAL: variable ID to inherit from parent>
Alias for “inherit-by-reference”

## Section
display: <on | off>
If a section's display is off, it will not be rendered on publish
A hidden section is a good location for declaring variables or defining sections whose output is dependent on logical conditions (for example, defining an error section which gets turned on in the corresponding PHP file based on certain conditions).

bind: <section-name>
Bind the visibility parameter to a section declared previously in the scope. Note that it MUST be defined previously since directives happen at compile-time.
Bound sections are useful for situations where only one of two sections will be displayed at a given time (for example, a success section and a failure section)
Any number of sections can be bound to each other.

loop-autoscope: <on | off> (ON BY DEFAULT)
Added for backwards compatibility. When looping over a section, each loop is given its own scope.
If you need to refer to a variable inside of a loop that was declared outside the section, set loop-autoscope to off and then scope manually

Example:
$$SYNTAX-LEVEL 2
*(manual-scope-example
   @(lambda {display: off}
      $(global-to-template { readonly | default: GLOBAL!})
   )
   
   ;; the loop!
   @(loop {loop-autoscope: off}
      Global variable within template: $(global-to-template)
      *(lambda ;; the manual scope! this must be done so each time through the loop we get a new variable rather than a reference
         $(loop-var) ;; since this is in its own template, it will be a new instance each time
      )
   )
)

disp: <on | off>
Alias for “display”

visible-if-defined: <identifier>
Binds Section visibility to a list of identifiers. If any identifier in the list evaluates to a non-truthy value, the section will mute itself

## Variable
default: <default value>
Sets a default value for the variable. This is the value that will be displayed if it's not set anywhere else

required
Give a runtime error if the page is published without providing a value for the variable

readonly
Don't allow a variable's value to be changed once it's set.

variable
Calls to replace will change the identifier rather than the value. These are useful inside of loops where a varying number of variables is being created for value replacements later on.

get-value: <identifier>
Looks up the value of identifier within the current scope and gives its value to the variable

val: <default value>
Alias for “default”

value: <default value>
Alias for “default”

req
Alias for “required”

rdonly
Alias for “readonly”

var
Alias for “variable”

get-val
Alias for “get-value”

## Code
language: <php> (default)
since code is just eval'd, it's possible to define other interpreters for code.
languages
php: Use the PHP interpreter as normally. The calling Code section is available through $this→reflect()
configuration: configuration source is a list of key-value pairs separated by a colon with each pair separated by a newline
$$SYNTAX-LEVEL 2
*(to-configure
   ;; this is the template that will get configured
   Hello there, from $(name) in $(location)!
)
$$SYNTAX-LEVEL 2
*(template
   ;; this template demonstrates how the configuration language interpreter can be used to configure an external template in-place
   *(to-configure {load: to-configure.bau}
      &(lambda {language: configuration}
         name: Bremen
         location: Ames
      )
   )
)
The preceding example will output
Hello there, from Bremen in Ames!
This is useful for generic structures where configuration is known ahead of time by the template author

eager
Evaluate code at compile-time

lazy
Evaluate code at runtime (default)

external: <file.php>
Loads and executes code from an external file in the interpreter defined by the Code section
This makes it so ugly escapes aren't needed for closing parentheses and other reserved Bauplan symbols
Note: Calls to “echo” will place the code above the desired point of insertion; use “return” instead

lang: <php> (default)
Alias for “language”

extern: <file.php>
Alias for “external”

## Keyword
argument: <arg>
Gives argument to keyword
Can give more than one keyword by calling the directive multiple times
Not all keywords accept arguments. Check the list of keywords to see which do and what the expected arguments are.\

arg: <arg>
Alias for “argument”
  
### PHP API

The Bauplan PHP code layout resembles the Bauplan grammar.

The main Bauplan object
The entry point for Bauplan is the Bauplan class which holds the document head.

<?php
include_once('Bauplan.php');
 
$bauplan = new Bauplan('Welcome'); # Create a new page with page title "Welcome";
?>
getHTML()
Return HTML, descending to all templates.

head($string=null)
Get or append to text in <head> tag

includeCss($css_path)
Include the CSS file given by $css_path. NOTE: In most cases, the .bau file should declare this via the “include-css” directive rather than doing it directly in the PHP code.

includeCssText($text)
Rather than including a CSS file, insert CSS directly. This method automatically supplies CSS tags.

includeInHeader($text)
Add $text to display between <head></head> tags verbatim.

includeScript($script_path, $type="text/javascript")
Include the script given by $script_path, by default setting the source type as “text/javascript”. NOTE: In most cases, the .bau file should declare this via the “include-js” directive rather than doing it directly in the PHP code.

includeScriptText($text, $type="text/javascript")
Rather than including a script, insert script code directly. This method automatically supplies script tags, defaulting to “text/javascript”.

preHTML($string=null)
Get or append to page data which is to be given before <html> tag

publish()
Print the page, descending to all templates.

template()
Get the main template object

title($title=null)
Get or set the page title

## The Template class
identifier()
Gives the identifier associated with this object

Return
The name of this object

upstream($identifier)
Search for $identifier upstream from this object

Return
The found object with ID $identifier or null

get($identifier)
Recursively search child nodes for object with ID $identifier

Return
The found object or throws fatal error if not found

has($identifier)
Recursively search child nodes for object with ID $identifier

Return
True or false depending on whether the type with ID $identifier can be found from the current node

load($resource)
Load the template file whose path is given by $resource into the current template.

Return
The newly created template object

set($template)
Add the template object specified by $template as a child of this template

Return
The template object set as a child of this template

replace($template)
Replace the current template by the template object $template

Return
The template object replacing the current template

loadStatic($file)
Load a file whose path is given by $file into this template without parsing $file's source

Return
The $file as an HTML object

loadRemote($url)
Load a template across the web with the given path $url. This will automatically locate and include any remote dependencies by making contextual calls remotely.

Return
The newly created template object

## The Section class
identifier()
Gives the identifier associated with this object

Return
The name of this object

upstream($identifier)
Search for $identifier upstream from this object

Return
The found object with ID $identifier or null

get($identifier)
Recursively search child nodes for object with ID $identifier

Return
The found object or throws fatal error if not found

has($identifier)
Recursively search child nodes for object with ID $identifier

Return
True or false depending on whether the type with ID $identifier can be found from the current node

bind($section)
Bind a section identified by $section to this section. Binding sections will bind visibility on the toggle method. For instance, if section A is defined with its display off while section B is defined with its display on and the two are bound, toggling section B will make section A display and vice-versa. If two sections are visible and bound, toggling one will turn both their displays off. If two sections are invisible and bound, toggling one will unmute both. Any number of sections may be bound together.

Return
The current section

mute()
Set the display for the current section to off

Return
The current section

unmute()
Set the display for the current section to on

Return
The current section

toggle()
Toggle the display of the current section and all sections bound to it

Return
The current section

unroll($identifier, $array)
Loop over the variable whose identifier is given by $identifier, replacing its value each time through the loop with the values in $array

Return
The current section

loop($keyvalue_array)
Loop over this section, each loop replacing the key given in $keyvalue_array with the corresponding value

Example
(template.bau)

*(template
   @(loop-section
      $(name)
      $(age)
   )
)
(logic)

<?php
include_once('Bauplan.php');
 
$bauplan = new Bauplan('Loop Example');
$bauplan->template()->load('template.bau');
$bauplan->get('loop-section')->loop(array(
   array(
      'name' => 'Bremen Braun',
      'age'  => 26,
   ),
   array(
      'name' => 'Scott Birkett',
      'age'  => 29,
   ),
   array(
      'name' => 'Bhavani Rao',
      'age'  => 26,
   ),
));
?>
Return
An array containing the sections created by the loop

copyLoop($iterations)
Do a standard copy of the loop $iterations times, copying the loop verbatim

Return
The newly created sections

count()
Find the number of loops this section contains. A section by itself will have a count of 1, while a loop over n items will have a count of n-1 since the first loop places values into the original section.

Return
The number of loops this section contains.

## The Variable class
identifier()
Gives the identifier associated with this object

Return
The name of this object

replace($replacement)
Set the value of the variable to that given by $replacement

Return
The value of this variable after doing the replacement

append($value)
Append $value to the current value of this variable

Return
The value of this variable after appending

value()
Read the value held by this variable

Return
The value of this variable

hasBeenSet()
Check whether this variable has been given a value. A variable that has been given a default value using a directive will return “true” along with any values set in the PHP code

Return
True or false depending on whether the variable has been set

## The Code class
identifier()
Gives the identifier associated with this object

Return
The name of this object

execute()
Manually execute the code held in this Code object. Note that code that has not been executed will automatically be executed at runtime so it is almost never useful to call execute manually

Return
returns NULL unless return is called in the evaluated code, in which case the value passed to return is returned. If there is a parse error in the evaluated code, eval() returns FALSE and execution of the following code continues normally.

isExecuted()
Return whether or not the code in this Code object has already been executed.

Return
True if executed, else false
