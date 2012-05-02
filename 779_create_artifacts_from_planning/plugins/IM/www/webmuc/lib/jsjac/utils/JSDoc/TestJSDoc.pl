#!/usr/bin/perl

package JSDoc;
#
# Unit testing of JSDoc
#
# Run with 'perl TestJSDoc.pl' or './TestJSDoc.pl'
#

use strict;
use warnings;



use JSDoc;
use Data::Dumper;
use Test::More qw(no_plan);

$|++;

# parse_jsdoc_comment
diag("Testing parse_jsdoc_comment");
is_deeply(parse_jsdoc_comment(''), {summary => ''}, 
    "Ensure only summary is filled in");

is(parse_jsdoc_comment('')->{summary}, '', 'Empty comment value');


is(parse_jsdoc_comment(
        '************************* test *************')->{summary},
    'test', 'long leading and trailing stars');


# annotate_comment
diag("Testing annotate_comment");
is(annotate_comment, "\n/** \@private */", 'annotate_comment w/o arg');
like(annotate_comment("/** This is a test */"), 
        qr#^/\s*\*\*\s*This is a test\s+\@private\s*\*/\s*$#, 
        'annotate_comment w/ arg');
like(annotate_comment("/** This is a test */", '@testtag value'),
        qr#^/\s*\*\*\s*This is a test\s+\@testtag\svalue\s*\*/\s*$#, 
        'annotate_comment w/ tag argument');

# find_balanced_block
diag("Testing find_balanced_block");
my @blocks = (
        # basic simple input
        ['{', '}', '{ this is in the braces } {this is after}{{', 
            ['{ this is in the braces }', ' {this is after}{{'] ,
            'basic input'],
        
        # discard leading chars before opening char
        ['{', '}', 'discard {inner} after',
            ['{inner}', ' after'], 'discard leading chars'],

        # empty input string
        ['{', '}', '',
            ['', ''], 'empty input string'],

        # nothing to match at all
        ['{', '}', 'there is nothing to match', 
            ['', 'there is nothing to match'], 'nothing to match'],
    );
for my $test (@blocks){
    my @args = @{$test}[0..2];
    my ($expect, $explain) = @{$test}[3,4];
    is_deeply([find_balanced_block(@args)], $expect, $explain);
}

#
# Test the @member tag
#
diag('Testing the @member tag');
reset_parser();
my $src = q#
/** @constructor */
function Foo(){
    this.x = function(){return null;};
}
/** Unrelated */
function myfunc(){return null;}
#;
my $classes = parse_code_tree(\$src);
my %method_names = map { $_->{mapped_name} => 1 } 
            @{$classes->{Foo}->{instance_methods}};
ok(not(defined($method_names{myfunc})), 
    'Unrelated method is not added to class without @member tag');
reset_parser();
$src = q#
/** @constructor */
function Foo(){
    this.x = function(){return null;};
}
/** 
 * @member Foo
 */
function myfunc(){return null;}
#;
$classes = parse_code_tree(\$src);
%method_names = map { $_->{mapped_name} => 1 } 
            @{$classes->{Foo}->{instance_methods}};
ok(defined($method_names{myfunc}), 
    'Add method marked with @member to class');
reset_parser();

# 
# preprocess_source
#

diag("Testing preprocess_source");

# Make sure that:
#
#  Foo.prototype = { 
#     bar: function(){ return "Eep!"; },
#     baz: "Ha!"
#  } 
#
#  becomes:
#
#  Foo.prototype.bar = function(){ return "Eep!"; };
#  Foo.prototype.baz = "Ha!";

my $before = q/
  Foo.prototype = { 
     bar: function(){ return "Eep!"; },
     baz: "Ha!"
  } /;

my $after_re = qr/^\s*(?:$JSDOC_COMMENT)?\s*Foo.prototype.bar
                            \s*=\s*
                            function\(\s*\)\s*\{[^\}]*}\s*;\s*
                            Foo\.prototype\.baz\s*=\s*"[^"]+"\s*;\s*$/x;

like(preprocess_source($before), $after_re, 
    'Unpack prototype block assignment');

# 
# Make sure that:
#
#     /** @constructor */
#     Foo.Bar = function(){this.x = 2;var y = 3;}
# becomes:
#     /** @constructor */
#     Foo.Bar = function(){};
#     
#     /** @constructor */
#     function Foo.Bar(){}
#
#     Foo.Bar.prototype.x = 2;
#
#     /** @private */
#     Foo.Bar.prototype.y = 3;
#
$before = q#
     /** @constructor */
     Foo.Bar = function(){this.x = 2; var y = 3; }#;
$after_re = qr{
     ^\s*/\*\*\s*\@constructor\s*\*/\s*
     Foo\.Bar\s*=\s*function\s*\(\s*\)\s*\{\s*\}\s*;\s*
     /\*\*\s*\@constructor\s*\*/\s*
     function\s+Foo\.Bar\s*\(\s*\)\s*\{\s*\}
     \s* 
     Foo\.Bar\.prototype\.x\s*=\s*2\s*;\s*
     /\*\*\s*\@private\s*\*/\s*
     Foo\.Bar\.prototype\.y\s*=\s*3\s*;\s*$
    }x;
like(preprocess_source($before), $after_re,
        'Unpack nested class');

#
# Make sure that:
#       MySingleton = new function(){this.x=function(){}}
#   and
#       var MySingleton = new function(){this.x=function(){}}
# become:     
#       function MySingleton(){}
#       MySingleton.prototype.x = function(){};
#
$before = q# MySingleton = new function(){this.x=function(){}} #;
$after_re =  qr{
        ^\s*(?:$JSDOC_COMMENT)?
        \s*function\s*MySingleton\s*\(\)\s*\{\s*\}\s*
        (?:$JSDOC_COMMENT)?\s*
        MySingleton\.prototype\.x\s*=\s*function\s*\(\s*\)\s*\{\s*\}\s*;\s*$}x;
like(preprocess_source($before), $after_re,
        'Unpack singleton');

# Same thing, but with var before the declaration
$before = q#var MySingleton = new function(){this.x=function(){}} #;
like(preprocess_source($before), $after_re,
        "Unpack var'd singleton");


# 
# Test unpacking a constructor into a bunch of 
# prototype-based declarations
#

$before = q#
    /**
     * @constructor 
     */
    function MyClass(){
        /** Private variable 'x' */
        var x = 3;
        /**
         * This is my function
         */
        this.myFunction = function(){ return null; };

        /**
         * This is a private function
         */
        function myPrivateFunction(x){
            return null;
        }
    }
#;
$after_re = qr{
    /\*\*\s*
     \*\s*\@constructor\s*
     \*/\s*
    function\s+MyClass\s*\(\s*\)\s*\{\s*\}\s*
    
    /\*\*\s*Private\svariable\s'x'\s*
    \@private\s*\*/\s*
    MyClass\.prototype\.x\s*=\s*3\s*;\s*

    /\*\*\s*
    \*\s*This\sis\smy\sfunction\s*\*/\s*
    MyClass\.prototype\.myFunction\s*=\s*function\s*\(\s*\)\s*\{ 
        [^\}]*\}\s*;\s*
        
    /\*\*\s*
     \*\s*This\sis\sa\sprivate\sfunction\s*
      \@private\s*\*/\s*
    MyClass\.prototype\.myPrivateFunction\s*=\s*function\(\s*x\s*\)\s*
    \{[^\}]*\}\s*$
}x;

like(preprocess_source($before), $after_re, 
    'Testing unpacking a constructor into prototype-based assignments');


#
# Test the marking of void methods
#
$before = q'function MyFunc(){}';
$after_re = qr{/\*\*\s*\@type\s+void\s*\*/\s*function\s+MyFunc\s*\(\)\{\}};
like(preprocess_source($before), $after_re,
   "Testing basic marking of void method without a docstring");

$before = q'
/** Method */
function MyFunc(){}
';
$after_re = qr{/\*\*\s*Method\s+\@type\s+void\s*\*/\s*
                function\s+MyFunc\(\)\{\}}x;
like(preprocess_source($before), $after_re,
    "Testing basic marking of void methods");

$before = '/** Method */
            Shape.prototype.MyFunc = function(){}';
$after_re = qr{
    /\*\*\s*
        Method\s+
        \@type\s+void\s*
    \*/\s*Shape\.prototype\.MyFunc\s*=\s*function\(\)\{\}}x;
like(preprocess_source($before), $after_re,
    "Testing marking of void anonymous method");

$before = 'Shape.prototype.MyFunc = function(){return null;}';
$after_re = qr{^\s*Shape\.prototype\.MyFunc\s*=
                \s*function\(\)\{[^\}]*\}}x;
like(preprocess_source($before), $after_re,
    "Testing marking of void anonymous method");

$before = "function x(){return null;}";
$after_re = qr{\s*function\sx\(\)\s*\{[^\}]*\}\s*$};
like(preprocess_source($before), $after_re,
    "Leave non-void methods without docstrings alone");

$before = "/** My test function */\nfunction x(){return null;}";
$after_re = qr{\s*/\*\*\s*My\stest\sfunction\s*\*/\s*
                function\sx\(\)\s*\{[^\}]*\}\s*$}x;
like(preprocess_source($before), $after_re,
    "Leave non-void methods with docstrings alone");

reset_parser();
$src = q#
/**
 * @constructor
 */
function MyClass(){
    this.af = afunc;
    this.bf = bfunc;
    this.cf = cfunc;
    function afunc(){}
    function bfunc(){}
    function cfunc(){}
}
#;
$classes = parse_code_tree(\$src);
ok(eq_set(
        [ map { $_->{mapped_name} }
            @{$classes->{MyClass}->{instance_methods}}],
        ['af', 'bf', 'cf', 'afunc', 'bfunc', 'cfunc']),
    "Ensure instance methods in constructor are correctly assigned");
   


reset_parser();
$src = 'function MyFunction(){ return ""; }';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{GLOBALS}->{class_methods}->[0]->{vars}->{type}),
    "Ensure a function returning an empty string is not marked as void");

reset_parser();
$src = 'function A(){ var x = "x"; }';
$classes = parse_code_tree(\$src);
ok($classes->{GLOBALS}->{class_methods}->[0]->{vars}->{type}->[0] eq 'void',
    "Ensure a global void is void");

reset_parser();
$src = 'function A(c){ c.someFunc = function(){ return 2; }; }';
$classes = parse_code_tree(\$src);
ok($classes->{GLOBALS}->{class_methods}->[0]->{vars}->{type}->[0] eq 'void',
    "Ensure inner function definitions don't affect the return type");

reset_parser();
$src = 'function A(c){ c.someFunc = function(){ return 2; }; return ""; }';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{GLOBALS}->{class_methods}->[0]->{vars}->{type}->[0]),
    "Ensure inner-function measures don't affect non-void functions");

reset_parser();
$src = '/** @return {int} Description */function f(){}';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{GLOBALS}->{class_methods}->[0]->{vars}->{type}->[0]),
    'Methods with a @return tag but no return statement are not marked void');

reset_parser();
$src = 'function f(){ return (true ? "t" : "f");}';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{GLOBALS}->{class_methods}->[0]->{vars}->{type}->[0]),
    "Non-void with non-trivial return statement is not marked as void");

#
# Try huge constructor input
#
my @testsrc = (q#
/**
 * @class This is class information
 * @constructor
 */
 function MyClass(){

#);
for (1..30){
    push @testsrc, "
    /** This is a private method */
    function f$_(){ return null; }

    /**
     * THis is function number $_
     * \@return Nothing
     */
    this.func$_ = function(){if(true){if(false){return null;}}} ;\n";
}
push @testsrc, "\n}\n";
my $testsrc = join("\n", @testsrc);
# This could crash everything
preprocess_source($testsrc);
pass("Process huge constructor with preprocess_source");


#
# Huge constructor with unbalanced input
#
@testsrc = (q#
/**
 * @class This is class information
 * @constructor
 */
 function MyClass(){

#);
for (1..100){
    push @testsrc, "
    /**
     * THis is function number $_
     * \@return Nothing
     */
    this.func$_ = function(){if(true){if(false){return null;}};\n";
}
push @testsrc, "\n}\n";
$testsrc = join("\n", @testsrc);
# This could crash everything
preprocess_source($testsrc);
pass("Process huge unbalanced constructor with preprocess_source");

#
# deconstruct_mozilla_getset
#
$before = 'MyClass.prototype.__defineGetter__("myProp", function(){return null;});';
$after_re = qr{
   ^\s*MyClass\.prototype\.myProp\s*=\s*null\s*;\s*$}x;
   #\s*function\s*\(\s*\)\s*\{\s*return\s+null\s*;\s*\}\s*;\s*$}x;

like(deconstruct_getset($before), $after_re,
   "Testing behaviour of __defineGetter__");
like(preprocess_source($before), $after_re,
   "Testing behaviour of __defineGetter__ in preprocess_source");

$before = 'MyClass.prototype.__defineSetter__("myProp", function(){return null;});';
$after_re = qr{
   ^\s*MyClass\.prototype\.myProp\s*=\s*null\s*;\s*$}x;

like(deconstruct_getset($before), $after_re,
   "Testing behaviour of __defineSetter__");
like(preprocess_source($before), $after_re,
   "Testing behaviour of __defineSetter__ in preprocess_source");

reset_parser();
$src = "
    function MyFunc(theclass){
        var x = 2;
        theclass.prototype.f = function(){};
        return x;
    }
    MyClass.prototype.f = function(){};
";
$classes = parse_code_tree(\$src);
ok(not(defined($classes->{theclass})), 
    "Ensure that dynamic prototyping doesn't add classes");
ok(defined($classes->{MyClass}), 
    "Ensure that normal classes are added with static prototyping");


# Test @singleton handling
reset_parser();
$src = q# 
    /** @singleton */
    var SingletonClass = {
        funcA: function(){},
        funcB: function(){} };
#;
$classes = parse_code_tree(\$src);
ok(defined($classes->{SingletonClass}));
my @fnames = sort map { $_->{mapped_name}}  
    @{$classes->{SingletonClass}->{instance_methods}};
is(scalar(@fnames), 2);
ok(eq_array(\@fnames, ["funcA", "funcB"]));


#
# miscellaneous tests
# 
diag("Miscellaneous tests");
reset_parser();
$src = "
    /** \@constructor */
    function A(){}
    /** \@constructor */
    function C(){}
    /** \@constructor
    \@extends A
    */
    function B(){}
    B.prototype = new C();";

$classes = parse_code_tree(\$src);
is($classes->{B}->{extends}, 'A', 
    "Test that the first extends marking is the good one, others are ignored");

reset_parser();
$src = "function A(){ this.n = function(){return 2};}
        var a = new A(); ";
$classes = parse_code_tree(\$src);
ok(defined($classes->{A}), 
    "Functions are later used with 'new' must be treated as a constructor");

ok(!defined($classes->{this}), "'this' cannot be added as a class");


#
# Ensure using the @base tag automatically qualifies a function as a class,
# even if the base class isn't defined
#
reset_parser();
$src = '/** @base SomeOtherClass */
function MyClass(){}';
$classes = parse_code_tree(\$src);
ok(defined($classes->{MyClass}), 
    'A function must be upgraded to a class if the @base tag is used');

#
# Allow an anonymous function to be assigned to a global variable,
# resulting in a new class
#
reset_parser();
$src = '
/**
 * Some function
 * @constructor
 */
var SomeClass = function(){ this.x = 2; }
';
$classes = parse_code_tree(\$src);
ok(defined($classes->{SomeClass}),
    "Allow anonymous function to be assigned to a global variable");

#
# Make sure that dynamically binding methods to a object at a later time
# do not affect the documentation
#
reset_parser();
$src = '
function AddCallback(obj){
    obj.callback = function(){ return null; };
}';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{obj}), 
    "Don't add passed-in objects as classes when doing dynamic binding");

reset_parser();
$src = '
/** @constructor */
function A(){}
A.prototype.setup = A_Setup;
A.prototype.tearDown = A_TearDown;
function A_Setup(){
    this.callback = function(){ return null; };
}
function A_TearDown(){
    this.tornDown = true;
}';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{this}),
    "Don't add 'this' as a class when dynamically adding methods in a method");

#
# Test block prototype assignment
#
diag("Test block prototype assignment");
reset_parser();
$src = '
SomeClass.prototype = {
    funcA: function(){ return null; },
    valA: 3,
    funcB: function(){ return null; },
    valB: "just testing",
    funcC: function(){}
};';
$classes = parse_code_tree(\$src);
ok(eq_set(
        [ map { $_->{mapped_name} }
            @{$classes->{SomeClass}->{instance_methods}}],
        ['funcA', 'funcB', 'funcC']),
    "Ensure instance methods are assigned in prototype definition block");
ok(eq_set(
        [ map { $_->{field_name} }
            @{$classes->{SomeClass}->{instance_fields}}],
        ['valA', 'valB']),
    "Ensure instance fields are assigned in prototype definition block");

#
# Test prototype assignment
#
diag("Test prototype assignment");
reset_parser();
$src = '
function Base(){}
function Sub(){}
Sub.prototype = new Base();
';
$classes = parse_code_tree(\$src);
ok($classes->{Sub}->{extends} eq 'Base',
    "Prototype assignment results in inheritance");

reset_parser();
$src = '
function Base(){}
function Sub(){}
Sub.prototype = new Base;
';
$classes = parse_code_tree(\$src);
ok($classes->{Sub}->{extends} eq 'Base',
    "Prototype assignment results in inheritance (2)");

#
# Test the handling of methods defined more than once
#
reset_parser();
$src = '
function f(){}
/** doc */
function f(){}
';
$classes = parse_code_tree(\$src);
ok($classes->{GLOBALS}->{class_methods}->[0]->{description} eq 'doc',
    "In case of double function definition, the one with most info wins");

reset_parser();
$src = '
/** doc */
function f(){}
function f(){}
';
$classes = parse_code_tree(\$src);
ok($classes->{GLOBALS}->{class_methods}->[0]->{description} eq 'doc',
    "In case of double function definition, the one with most info wins (2)");

#
# Make sure that extra JSDoc-style comment blocks are not viewed as source
#
reset_parser();
$src = '
/** @constructor */
function x(){}

/** more doc 
function y(){}
*/

/** @constructor */
function z(){}
';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{GLOBALS}->{class_methods}->[0]),
    "Ignore JSDoc in extra JSDoc-comment blocks");


#
# Test the behaviour of the @ignore tag
#
reset_parser();
$src = '
/** This method is normal */
function Normal(){}

/** @ignore */
function Hidden(){}
';
$classes = parse_code_tree(\$src);
my %fnames = map { $_->{mapped_name} => 1 }
    @{$classes->{GLOBALS}->{class_methods}};
ok(defined $fnames{Normal}, "A normal method is picked up and documented");
ok(!defined $fnames{Hidden}, 'An @ignored method is not picked up');

#
# Test the behaviour of the @addon tag
#
reset_parser();
$src = '
/** 
 * Should be ignored
 */
ClassOne.funcOne = function(){};

/**
 * Should not be ignored
 * @addon
 */
ClassTwo.funcOne = function(){};

ClassThree.prototype = new Object();
ClassThree.funcThree = function(){}';
$classes = parse_code_tree(\$src);
ok(!defined($classes->{ClassOne}), 
    'Extensions to undefined classes/objects without @addon are ignored');
ok(defined($classes->{ClassTwo}),
    'Extensions to undefined classes/objects with @addon are not ignored');
ok($classes->{ClassThree}->{class_methods}->[0]->{mapped_name} eq 'funcThree',
    'Class methods without @addon work on pre-defined classes');

#
# Ensure enclosing package-classes are still recognized without using @addon
# 
reset_parser();
$src = '
/**
 * @constructor
 */
package.MyClass = function MyClass(){}

package.MyClass.prototype.foo = function foo(){}
';
$classes = parse_code_tree(\$src);
ok(defined($classes->{package}), 
    'Super-package-classes must be recognized without the @addon tag');
ok(defined($classes->{'package.MyClass'}),
    'Sub-package-classes must be recognized without the @addon tag');


