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
Escape Character
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
