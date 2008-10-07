package JSDoc;

=head1 NAME

JSDoc - parse JavaScript source file for JSDoc comments

=head1 SYNOPSIS

Create JavaScript sourcefiles commented in a manner similar to javadoc
(ie. with documentation starting with '/**' and then pass a list of references 
to JavaScript source to parse_code_tree:

   /**
    * This is a class for example purposes
    * @param name Name for the new object
    * @constructor
    */
    function MyClass(name){
      this.name = name;
    }

   $code_tree = parse_code_tree(@src_refs);

A tree structure describing the code layout, inheritance and documentation
is returned

To clear the cache of classes and functions in the parser:

   reset_parser();
    

=head1 DESCRIPTION

The C<parse_code_tree> function requires a ref to a string holding the
souce code of a javascript object file or files. It returns a data structure
that describes the object hierarchy contained in the source file, as well
as included documentation for all fields and methods. The resulting
data structure has the following form (for each class):

   Class
      |
      +- classname
      |
      +- constructor_args
      |
      +- extends  
      |
      +- constructor_detail
      |
      +- constructor_vars
      |
      +- class_methods
      |  |
      |  +- description
      |  |
      |  +- mapped_name
      |  |
      |  +- argument_list
      |  |
      |  +- vars 
      |
      +- instance_methods
      |  |
      |  +- description
      |  |
      |  +- mapped_name
      |  |
      |  +- argument_list
      |  |
      |  +- vars 
      | 
      +- class_fields
      |  |
      |  +- field_description
      |  |
      |  +- field_name
      |  |
      |  +- field_value
      |  |
      |  +- field_vars
      |
      +- instance_fields
      |  |
      |  +- field_description
      |  |
      |  +- field_name
      |  |
      |  +- field_value
      |  |
      |  +- field_vars
      |
      +- inner_classes
      |  |
      |  +- class_name
      |
      +- inherits
         |
         +- Class
            |
            +- instance_fields
            |
            +- instance_methods


There is also an additional entry under the key __FILES__ that contains
keyed entries for each @tag that was defined in the first JSDoc comment
block with a @fileoverview tag. Each entry under __FILES__ is keyed by
filename, and is a hash reference.

=head1 AUTHORS

Gabriel Reid gab_reid@users.sourceforge.net,
Michael Mathews michael@mathews.net

=cut

require 5.000;
use strict;
use warnings;
use Exporter;

# Recursion limit for recursive regexes
use constant RECURSION  => 10;

use vars qw/ @ISA @EXPORT /;

@ISA = qw(Exporter);
@EXPORT = qw(parse_code_tree configure_parser reset_parser);

# State
use vars qw/ %CLASSES %FUNCTIONS %CONFIG $CTX_FILE /;

# Regexes
use vars qw/ $BAL_PAREN $BAL_BRACE $BAL_SQUAR $SQUOTE $DQUOTE $NONQUOTE 
               $FUNC_DEF $RET_FUNC_DEF $ANON_FUNCTION $LITERAL $FUNC_CALL 
               $JSDOC_COMMENT $MLINE_COMMENT $SLINE_COMMENT /;

# This limits nested braces to 30 levels, but is much more 
# stable than using a dynamic regex
$BAL_BRACE      = qr/\{(?:[^\{\}])*\}/;
$BAL_PAREN      = qr/\((?:[^()])*\)/;
$BAL_SQUAR      = qr/\[(?:[^[\]])*\]/;
for (1..RECURSION){
    $BAL_BRACE  = qr/\{(?:[^\{\}]|$BAL_BRACE)*\}/;
    $BAL_PAREN  = qr/\((?:[^()]|$BAL_PAREN)*\)/;
    $BAL_SQUAR  = qr/\[(?:[^[\]]|$BAL_SQUAR)*\]/;
}
$SQUOTE         = qr{'[^'\\]*(?:\\.[^'\\]*)*'};
$DQUOTE         = qr{"[^"\\]*(?:\\.[^"\\]*)*"};
$NONQUOTE       = qr{[^"'/]};
$FUNC_DEF       = qr/function\s+\w+(?:\.\w+)*\s*$BAL_PAREN\s*$BAL_BRACE/;
$RET_FUNC_DEF   = qr/function\s+(\w+(?:\.\w+)*)\s*($BAL_PAREN)\s*($BAL_BRACE)/;
$ANON_FUNCTION  = qr/function\s*$BAL_PAREN\s*$BAL_BRACE/;
$LITERAL        = qr/$DQUOTE|$SQUOTE|\d+/;
$FUNC_CALL      = qr/(?:new\s+)?\w+(?:\.\w+)*\s*$BAL_PAREN/;
$JSDOC_COMMENT  = qr{/\*\*[^*]*\*+(?:[^/*][^*]*\*+)*/};
$MLINE_COMMENT  = qr{/\*[^*]*\*+(?:[^/*][^*]*\*+)*/};
$SLINE_COMMENT  = qr{//[^\n]*};


#
# Public function that returns a datastructure representing the JS classes
# and their documentation
#
sub parse_code_tree {

    &initialize_parser;

    #
    # This (I mean the "<<$_>>") is pretty hacky, but I've made it this 
    # way to maintain backwards compatibility with anyone who's automatically 
    # expecting this to work when they throw an array of refs to it. If you're
    # using this for your own work, please don't expect to be able to 
    # put the context file in like this in the future
    #
    for my $js_src (map { ref and ${$_} or "<<$_>>" } @_){
        if ($js_src =~ /^<<(.+)>>$/){
            $CTX_FILE = $1;
            next;
        }

        # perlify os line-endings
        $js_src =~ s/(\r\n|\r)/\n/g;

        &parse_file_info($js_src);
        $js_src = &preprocess_source($js_src);
        &fetch_funcs_and_classes($js_src);
    }

    &map_all_properties();
    &build_class_hierarchy(); 
    &set_class_constructors();
    &filter_globals;

    while (my ($classname, $class) = each %CLASSES){
        delete $class->{_class_properties};
        delete $class->{_instance_properties};
        $class->{classname} = $classname unless $classname eq '__FILES__';
    }
    return \%CLASSES;
}

#
# Parses up a a jsdoc comment into its component parts
# PARAM: The document string to be parsed
#
sub parse_jsdoc_comment {
    my ($doc, $raw) = @_; 
    
    # Remove excess '*' characters
    $doc =~ s/^[*\s]*([^*].*?)[*\s]*$/$1/s;
    $doc =~ s/^\s*\*//gm;

    my %parsed = (); # remember each part that is parsed

    # the first paragraph could be a summary statement
    # a paragraph may follow of variable defs (variable names start with "@")
    my ($summary, $variable_str) = $doc =~ 
        /^\s*
        (
            (?:[^{@]|(?:\{[^@]))*
            (?:\{\@
            (?:[^{@]|(?:\{[^@]))*)*
        )
        \s*
        (.*)
        $/xs;
    $summary =~ s/^\s*(\S.*?)\s*$/$1/s;
    $parsed{summary} = $summary;

    # two types of variable def can be dealt with here:
    # a @argument has a two-part value -- the arg name and a description
    # all other @<variables> only have a single value each (although there may
    # be many variables with the same name)
    if($variable_str) {
        my %vars = ();
        while ($variable_str =~ /
            (?<![\{\w])\@(\w+)      # The @attribute, but not a {@link}
            \s*
            ((?:\{\@|\w\@|[^\@])*)   # Everything up to the next @attribute
            /gsx) {
            my ($name, $val) = ($1, $2);
            $vars{$name} = [] unless defined $vars{$name};
            $val =~ s/\n/ /g unless $raw or $name eq 'class';
            push(@{$vars{$name}}, ($val =~ /^\s*(.*)\s*$/s)[0]);
        }
        $parsed{vars} = \%vars;
    }
    return \%parsed;
}

#
# Builds up the global FUNCTION and CLASSES hashes
# with the names of functions and classes
#
sub fetch_funcs_and_classes {
    my $js_src = shift;
   
    while ($js_src =~ m!
        # Documentation
        (?:
            /\*\*                         # Opening of docs
                ([^/]+
                    (?:(?:[^*]/)+[^/]+)*
                )
            \*/\s*\n\s*                   # Closing of docs
        )?
         
        # Function
        (?:(?:function\s+(\w+)\s*(\(.*?\))\s*\{)|         
         
        # Anonymous function
        (?:(\w+(?:\.\w+)*?)(\.prototype)?\.(\w+)\s*=\s*function\s*
                                                        (?:\w+\s*)?(\(.*?\)))|  

        # Instance property 
        (?:(\w+(?:\.\w+)*?)\.prototype\.(\$?\w+)\s*=\s*(.*?)\s*[;\n])|    

        #Inheritance
        (?:(\w+(?:\.\w+)*?)\.prototype\s*=
                           \s*new\s*(\w+(?:\.\w+)*?)(?:\(.*?\))?\s*[;\n])| 

        # Class property
        (?:(\w+(?:\.\w+)*?)\.(\$?\w+)\s*=\s*(.*?)\s*[;\n]))        
    !gsx){
    
        my ($doc, $fname1,  $arglist1, $cname1, $prototype, $fname2, $arglist2)
            = ($1 || '', $2, $3, $4, $5, $6, $7);
        my ($cname2, $propname1, $propval1) = ($8, $9, $10);
        my ($cname3, $baseclass) = ($11, $12);
        my ($cname4, $propname2, $propval2) = ($13, $14, $15);
        next if $doc =~ /\@ignore\b/;

        if ($fname1){
            &add_function($doc, $fname1, $arglist1);
            if ($doc =~ /\@(?:constructor|class|base)\b/){
                # Add all @constructor and @class methods as classes
                &add_class($fname1, $doc);
            } 
        } elsif ($cname1 && $fname2 && $FUNCTIONS{$cname1}){
            # Anonymous functions added onto a class or class prototype
            &add_anonymous_function($doc, $cname1, $fname2, 
                $arglist2, not defined($prototype));

        } elsif ($cname1 && $fname2 && not defined($FUNCTIONS{$cname1})){
            if ($doc =~ /\@(addon|base|constructor)\b/ 
                    || $prototype || $CLASSES{$cname1}){
                # Called for methods added to the prototype of core classes
                &add_anonymous_function($doc, $cname1, $fname2, 
                    $arglist2, not defined($prototype));
            }

        } elsif ($cname2 && $propname1 && defined($propval1)) {
            &add_property($doc, $cname2, $propname1, $propval1, 0);
        } elsif ($cname3 && $baseclass){
            &set_base_class($cname3, $baseclass);
        } elsif ($cname4 && $propname2 && defined($propval2) 
                    && $propname2 ne 'prototype' 
                    && $cname4 ne 'this'){
            
            if ($FUNCTIONS{$cname4} 
                || $CLASSES{$cname4} 
                || $js_src =~ /function\s*\Q$cname4\E\b/){
                    &add_property($doc, $cname4, $propname2, $propval2, 1);
            }
        }
    }
}

#
# Add a function that is given as Class.prototype.blah = function(){...}
#
sub add_anonymous_function {
    my ($doc, $class, $function_name, $arg_list, $is_class_prop) = @_;

    # Just get out if the class is called 'this'. Reason for this is that
    # binding methods to 'this' should already be converted to binding methods
    # to the prototype, therefore binding to 'this' is only possible in
    # member functions, and is therefore not considered static or consistent
    # enough to be documented.
    return unless $class ne 'this';
    &add_class($class);
    my $fake_name = "__$class.$function_name";
   
    # This is dirty
    my $is_private = $doc =~ /\@private\b/;

    &add_function($doc, $fake_name, $arg_list, $is_private) and
        &add_property($doc, $class, $function_name, $fake_name, $is_class_prop);
    
    &add_class("$class.$function_name", $doc) 
        if $doc =~ /\@(?:constructor|class)\b/;
}


# 
# Add a class to the global CLASSES hash
#
sub add_class {
    my $class = shift;
    warn "Can't add 'this' as a class, please file a bug report!" 
        if $class eq 'this';
    my $class_doc = shift || '';
    if (!$CLASSES{$class}){
        $CLASSES{$class} = {};
        $CLASSES{$class}->{$_} = [] for qw(instance_fields class_fields 
                                            instance_methods class_methods
                                            inner_classes);
    }
    unless ($CLASSES{$class}->{extends}){
        &set_base_class($class, $1) 
            if $class_doc =~ /\@base\s+(\w+(?:\.\w+)*)/;
    }
}

#
# Set the base class for a given class
#
sub set_base_class {
    my ($class, $base_class) = @_;
    &add_class($class);
    $CLASSES{$class}->{extends} = $base_class
        unless $CLASSES{$class}->{extends};
}

#
# Add a property, either a class or instance method or field
#
sub add_property {
    my ($doc, $class, $property, $value, $is_class_property) = @_;
    &add_class($class);
    return if $property eq 'constructor';
    my $parsed_doc = &parse_jsdoc_comment($doc);
    $doc = $parsed_doc->{summary};
    my $key = $is_class_property ? '_class_properties' : '_instance_properties';
    for my $classref (@{$CLASSES{$class}->{$key}}){
        if ($classref->{property_name} eq $property){
            # Whine about rebinding functions to classes
            if ($FUNCTIONS{$value}){
                warn "Already bound property '$property' to '$class'\n";
                return;
            }
            
            # Only take on new attributes 
            $classref->{property_doc} ||= $doc;
            $classref->{property_vars} ||= $parsed_doc->{vars};
            return;
        }
    }

    push @{$CLASSES{$class}->{$key}}, {
        property_doc => $doc,
        property_name => $property,
        property_value => $value,
        property_vars => $parsed_doc->{vars} 
    };
}


#
# Add a function and its documentation to the global FUNCTION hash
#
sub add_function {
    my ($doc, $function, $arg_list, $is_private) = @_;

    # clean remaining comments out of arg list
    # (all others are already gone)
    # Again, taken from Jeffrey Friedl's "Mastering Regular Expressions"
    {
        no warnings;
        $arg_list =~ s/
            ($NONQUOTE+|
            $DQUOTE$NONQUOTE*|
            $SQUOTE$NONQUOTE*)
            |$MLINE_COMMENT|$SLINE_COMMENT/$1/gx;
    }

    if ($FUNCTIONS{$function}){
        warn "Function '$function' already declared\n";
        unless ($doc && !$FUNCTIONS{$function}->{documentation}->{summary}){
            return 0;
        }
    }
    $FUNCTIONS{$function} = {};
    my $func = $FUNCTIONS{$function};
    $arg_list and $func->{argument_list} = join(" ", split("\\s+", $arg_list))
        or $func->{argument_list} = "()";

    my $documentation = parse_jsdoc_comment($doc);
    if ($documentation->{vars}->{member}){
        my ($classname) = map { s/^\s*(\S*)\s*$/$1/; $_ } 
            @{$documentation->{vars}->{member}};
        &add_property($doc, $classname, $function, $function, 0)
            if $classname =~ /\w+/;
    }
    my $function_ref = $FUNCTIONS{$function};

    $function_ref->{documentation} = $documentation;
    $function_ref->{description} = $documentation->{summary};
    $function_ref->{vars} = $function_ref->{documentation}->{vars};
    $function_ref->{vars}->{filename} = $CTX_FILE;
    $function_ref->{vars}->{private} = 1 if $is_private;
    1;
}


#
# Map all the class and instance properties to their implementation
#
sub map_all_properties {
    for my $type (qw(_class_properties _instance_properties)){
        for my $class (keys %CLASSES){
            &map_single_property(
                $class,
                $_->{property_name},
                $_->{property_value},
                $_->{property_doc},
                $_->{property_vars}, $type eq '_class_properties') 
                    for @{$CLASSES{$class}->{$type}}
        }
    }

    # Map all the unattached functions
    my $classname = $CONFIG{GLOBALS_NAME} || 'GLOBALS';
    &add_class($classname);
    for my $function 
        (grep !($FUNCTIONS{$_}->{is_mapped} || $CLASSES{$_}), keys %FUNCTIONS){
            &map_single_property(
                $classname, $function, $function, '', undef, 1);
    }

    # Map static inner classes
    for $classname (keys %CLASSES){
        my $i = 0;
        my @to_remove;
        for my $cprop (@{$CLASSES{$classname}->{class_methods}}){
            my $propname = $cprop->{mapped_name};
            if ($CLASSES{"$classname.$propname"}){
                push @to_remove, $i;
                push @{$CLASSES{$classname}->{inner_classes}}, 
                    {class_name => "$classname." . $cprop->{mapped_name}};
                $FUNCTIONS{"$classname.$propname"} = 
                delete $FUNCTIONS{"__$classname.$propname"};
            } 
            $i++;
        }
        splice(@{$CLASSES{$classname}->{class_methods}}, $_, 1) 
            for reverse @to_remove;
    }
}

#
# Map a single instance or class field or method 
#
sub map_single_property {
    my ($class, $prop_name, $prop_val, 
    $description, $vars, $is_class_prop) = @_;
    if (!$FUNCTIONS{$prop_val}){
        push @{$CLASSES{$class}->{$is_class_prop 
                                    ? 'class_fields' : 'instance_fields'}}, { 
            field_name          => $prop_name,
            field_description   => $description,
            field_value         => $prop_val,
            field_vars          => $vars };
            return;
    }
    my %method;
    my $function = $FUNCTIONS{$prop_val};
    $function->{is_mapped} = 1;
    $method{mapped_name} = $prop_name;

    $method{$_} = $function->{$_} for 
        qw/ argument_list description vars /;

    push @{$CLASSES{$class}->{$is_class_prop 
                            ? 'class_methods' 
                            : 'instance_methods'}}, \%method;
}



#
# Build up the full hierarchy of classes, including figuring out
# what methods are overridden by subclasses, etc
# PARAM: The JS source code
#
sub build_class_hierarchy {
    # Find out what is inherited
    for my $class (map($CLASSES{$_}, sort keys %CLASSES)){
        my $superclassname = $class->{extends};
        !$superclassname and next;
        my $superclass = $CLASSES{$superclassname};
        $class->{inherits} = {};
        while ($superclass){
            $class->{inherits}->{$superclassname} = {};
            my @instance_fields;
            my @instance_methods;

            &handle_instance_methods($superclass, $superclassname, 
                                        $class, \@instance_methods);

            &handle_instance_fields($superclass, $superclassname, 
                                    $class, \@instance_fields);

            $superclassname = $superclass->{extends};
            $superclass = $superclassname ? $CLASSES{$superclassname} : undef;
        }
    }
}

#
# This is just a helper function for build_class_hierarchy
# because that function was getting way oversized 
#
sub handle_instance_methods {
    my ($superclass, $superclassname, $class, $instance_methods) = @_;
    if ($superclass->{instance_methods}){
        INSTANCE_METHODS: 
        for my $base_method (@{$superclass->{instance_methods}}){
            for my $method (@{$class->{instance_methods}}){
                if ($$base_method{mapped_name} eq $$method{mapped_name}){

                    # Carry over the description for overridden methods with
                    # no description (to be javadoc compliant)
                    if (($base_method->{description} or $base_method->{vars}) 
                            and not $method->{description}){

                        $method->{description} = $base_method->{description};
                        for my $varkey (keys(%{$base_method->{vars}})){
                            $method->{vars}->{$varkey} 
                                = $base_method->{vars}->{$varkey}
                                    unless $method->{vars}->{$varkey};
                        }
                    }
                    next INSTANCE_METHODS;
                }
            }
            for (keys %{$class->{inherits}}){
                my $inherited = $class->{inherits}->{$_};
                for my $method (@{$inherited->{instance_methods}}){
                    next INSTANCE_METHODS 
                        if $$base_method{mapped_name} eq $method;
                }
            }
            push @$instance_methods, $$base_method{mapped_name};
        }
        $class->{inherits}->{$superclassname}->{instance_methods} 
            = $instance_methods;
    }
}

#
# This is just a helper function for build_class_hierarchy
# because that function was getting way oversized 
#
sub handle_instance_fields {
    my ($superclass, $superclassname, $class, $instance_fields) = @_;
    if ($superclass->{instance_fields}){
        INSTANCE_FIELDS: 
        for my $base_field  (@{$superclass->{instance_fields}}){
            for my $field (@{$class->{instance_fields}}){
                next INSTANCE_FIELDS if $field eq $base_field;
            }
            push @$instance_fields, $base_field->{field_name};
        }
        $class->{inherits}->{$superclassname}->{instance_fields} 
            = $instance_fields;
    }
}

#
# Set all the class constructors
#
sub set_class_constructors {
    for my $classname (keys %CLASSES){
        my $constructor = $FUNCTIONS{$classname};
        $CLASSES{$classname}->{constructor_args} = 
        $constructor->{argument_list};
        $CLASSES{$classname}->{constructor_detail} 
            = $constructor->{description};

        $CLASSES{$classname}->{constructor_vars} = $constructor->{vars} || {};
    }
}

# 
# Clear out everything from the parsed classes and functions
#
sub reset_parser {
    %CLASSES = ();
    %FUNCTIONS = ();
}

#
# Set global parameters for the parser
#
sub configure_parser {
    %CONFIG = @_;
}

#
# Set the initial defaults for the parser
#
sub initialize_parser {
    $CONFIG{GLOBALS_NAME} ||= 'GLOBALS';
}

#
# Run through the source and convert 'confusing' JavaScript constructs
# into ones that are understood by the parser, as well as stripping
# out unwanted comment blocks. 
#
# For example:
#  
#  Foo.prototype = { 
#     bar: function(){ return "Eep!"; },
#     baz: "Ha!"
#  } 
#
#  becomes
#
#  Foo.prototype.bar = function(){ return "Eep!"; };
#  Foo.prototype.baz = "Ha!";
#
sub preprocess_source {
    my ($src) = @_;

    # Make all the @extends tags into @base tags
    $src =~ s/\@extends\b/\@base/g;

    # This had better not break anything!
    $src = &deconstruct_getset($src);

    # Convert:
    #     /** @singleton */
    #     Foo = {...};
    # to:
    #     /** @singleton */
    #     Foo = function(){}
    #     Foo.prototype = {...};
    $src =~ s!
        ($JSDOC_COMMENT\s*)
        (?:var\s*)?(\w+(?:\.\w+)*?)
        \s*=\s*{
        !index($1, '@singleton') == -1 ? "$1$2 = {" : "$1$2 = function(){}\n$2.prototype = {"!egx;

   
    # Convert:
    #     /** @constructor */
    #     Foo.Bar = function(){...}
    # to:
    #     /** @constroctor */
    #     Foo.Bar = function(){}
    #     /** @constructor */
    #     function Foo.Bar(){...}
    #
    # Note that we have to keep the original declaration so that Foo.Bar
    # can be recognized as a nested class. Yes, I know it's bad...
    $src =~ s!
        ($JSDOC_COMMENT\s*)
        (?:var\s*)?(\w+(?:\.\w+)*?)
        \s*=
        (\s*function)(?=\s*($BAL_PAREN)\s*\{)
        !index($1, '@constructor') == -1 ? "$1$2 = $3" : "$1$2 = function$4 {};\n$1$3 $2"!egx;

    # remove all uninteresting comments, but only if they're not inside
    # of other comments     
    # (adapted from Jeffrey Friedl's "Mastering Regular Expressions"
    {
       no warnings;
       $src =~ s/
          ($NONQUOTE+|
             $JSDOC_COMMENT$NONQUOTE*|
             $DQUOTE$NONQUOTE*|
             $SQUOTE$NONQUOTE*)
          |$MLINE_COMMENT|$SLINE_COMMENT/$1/gx;

        1 while $src =~ s/$JSDOC_COMMENT\s*($JSDOC_COMMENT)/$1/g;
    }

    # Alter the prototype-initialization blocks
    $src =~ s/
       (\w+(?:\.\w+)*)\.prototype
       \s*=\s*($BAL_BRACE)/deconstruct_prototype($1, $2)/egx;

    # Mark all constructors based on 'new' statements
    my %seen;
    my @classnames =  grep { not $seen{$_}++ } 
        $src =~ /\bnew\s+(\w+(?:\.\w+)*)\s*\(/g;
    for my $cname (@classnames){
        $src =~ s/($JSDOC_COMMENT?)
                    (?=\s*function\s+\Q$cname\E\s*$BAL_PAREN
                    \s*$BAL_BRACE)
                /&annotate_comment($1, '@constructor')/ex;
    }

    $src =~ s/
       ($JSDOC_COMMENT?)\s*
       (function\s+\w+(?:\.\w+)*\s*$BAL_PAREN\s*)
       ($BAL_BRACE)
       /&deconstruct_constructor($1, "$2$3")/egx;

    $src =~ s!
       (?:var\s+)?(\w+(?:\.\w+)*)
       \s*=\s*
       new\s+
       ($ANON_FUNCTION)!&deconstruct_singleton($1, $2)!egx;

    $src = &remove_dynamic_bindings($src);

    # Mark all void methods with "@type void"
    $src =~ s/($JSDOC_COMMENT?)\s*
       ((?:
          (?:function\s+\w+(?:\.\w+)*\s*$BAL_PAREN\s*)
          |
          (?:\w+(?:\.\w+)*\s*=\s*function\s*(?:\w+\s*)?$BAL_PAREN\s*)
       )$BAL_BRACE)
       /&mark_void_method($1, $2)/egx;

    # Clear nested functions (will save trouble later on)
    $src =~ s/($JSDOC_COMMENT?)\s*
       (\w+(?:\.\w+)*\s*=\s*)?
       (function(?:\s+\w+(?:\.\w+)*)?\s*$BAL_PAREN\s*)
       ($BAL_BRACE)
       /&clear_nested($1, $2, $3, $4)/egx;

    return $src;
}

sub clear_nested {
    my ($doc, $assign_to, $declaration, $funcbody) = @_;
    $assign_to ||= '';
    if ($doc =~ /^(?=.*\@constructor|\@class)(?=.*\@exec)/){
        warn "\@constructor or \@class can't be used together with \@exec\n";
    }
    if ($doc !~ /\@(constructor|class|exec)/ 
            or $assign_to =~ /^\w+\.prototype\./) {
        return "$doc\n$assign_to$declaration" . "{}\n";
    } elsif ($doc =~ /\@(constructor|class)/) {
        return "$doc\n$assign_to$declaration$funcbody";
    } else {
        my @visible_funcs = $funcbody =~ /
                    ((?:$JSDOC_COMMENT)?
                    (?:\w+\.\w+(?:\.\w+)*)\s*=\s*
                    (?:
                        $FUNC_DEF
                        |
                        $ANON_FUNCTION
                        |
                        $FUNC_CALL
                        |
                        \w+
                    )\s*;)/gx;
        return join("\n", @visible_funcs);
    }
}

#
# Remove dynamic binding.
# Change this:
#
#   function MyFunc(class){
#       var x = 2;
#       class.prototype.func = function(){};
#       return x;
#   }
#
# to:
#
#   function MyFunc(class){
#       var x = 2;
#
#       return x;
#   }
#
# and change this:
#
#   function BindMethod(obj){
#       obj.someFunc = function(){ return null; };
#       return obj;
#   }
#
# to:
#
#   function BindMethod(obj){
#
#       return obj;
#   }
#
sub remove_dynamic_bindings {
    my ($src) = @_;
    while ($src =~ /$RET_FUNC_DEF/g){
        my ($fname, $params, $definition) = ($1, $2, $3);
        next unless $definition =~ /\bfunction\b|\w+\.prototype\./;
        my @params = split(/\s*,\s*/, substr($params, 1, length($params) - 2));
        for my $param (@params){
            $src =~ s/\b$param\.prototype\.[^;\n]*[;\n]//g;
            $src =~ s/\b$param\.\w+ = $ANON_FUNCTION//g;
        }
    }
    $src;
}

#
# Annotate a method as being void if no @type can be found, and there is no
# statement to return a value in the method itself.
#
sub mark_void_method {
    my ($doc, $funcdef) = @_;
    return "$doc\n$funcdef" if $doc =~ /\@(constructor|type|returns?)\b/;    
    my ($fbody) = $funcdef =~ /^[^{]*($BAL_BRACE)/;
    $fbody =~ s/$FUNC_DEF/function x(){}/g;
    $fbody =~ s/$ANON_FUNCTION/function (){}/g;
    $doc = &annotate_comment($doc, '@type void') 
        if $fbody !~ /\breturn\s+(?:(?:\w+)|(?:(["']).*?\1)|$BAL_PAREN)/;
    "$doc\n$funcdef";
}

#
# A helper to change singleton declarations like
# 
#     MySingleton = new function(){this.x=function(){}}
#
# into:
#
#     function MySingleton(){}
#     MySingleton.prototype.x = function(){};
#
sub deconstruct_singleton {
    my ($classname, $definition) = @_;
    $definition =~ s/function\s*$BAL_PAREN\s*\{(.*)\}\s*$/$1/s;
    $definition =~ s/\bthis\./$classname.prototype\./g;
    qq#
        function $classname(){}
        $definition; 
    #;
}

#
# A helper to preprocess_source, change prototype-initialization
# blocks into multiple prototype-property assignments
#
sub deconstruct_prototype {
    my ($class, $src) = @_;
    $src =~ s/^\{(.*)\}$/$1/s;
    $src =~ s!
        (\w+)
        \s*:\s*
        (
        $ANON_FUNCTION
        |
        $FUNC_DEF
        |
        $FUNC_CALL
        |
        $LITERAL
        |
        $BAL_SQUAR
        |
        $BAL_BRACE
        |
        [-\w.+]+
        )
        \s*,?
    !$class.prototype.$1 = $2;!gsx;
    
    $src;
}

#
# Unpacks a constructor into separate calls
#
sub deconstruct_constructor {
    my ($doc, $func_def) = @_;
    return "$doc$func_def" unless $doc =~ /\@(constructor|class)/;
    my ($classname) = $func_def =~ /function\s+(\w+(?:\.\w+)*)/;
    $func_def =~ s/
        (\{.*\})$
        /&deconstruct_inner_constructor($classname, $1)/esx;
    "$doc$func_def";
}

sub deconstruct_inner_constructor {
    my ($classname, $inner_src) = @_;
    $inner_src = substr($inner_src, 1, -1);
    my @doc_n_fnames = $inner_src =~ /($JSDOC_COMMENT?)\s*function\s+(\w+)/g;

    unless ($CONFIG{NO_LEXICAL_PRIVATES}){
        $inner_src =~ s/
            ($JSDOC_COMMENT)?
            \s*
            var
            \s+
            (\w+)/&annotate_comment($1) . "\n$classname\.prototype\.$2"/egx;

        $inner_src =~ s/
            ($JSDOC_COMMENT)?\s*
            ^\s*
            function
            \s+
            (\w+)
            /&annotate_comment($1) . 
                "\n$classname\.prototype\.$2 = function"/egmx;
    }

    { 
        no warnings;
        $inner_src =~ s/
            ($JSDOC_COMMENT\s*)?
            ^\s*this(?=\.)
            /$1$classname.prototype/gmx;
    }

    # Replace all bindings of private methods to public names
    for (my $i = 0; $i < @doc_n_fnames; $i += 2)
    {
        my ($doc, $fname) = @doc_n_fnames[$i, $i + 1];
        $inner_src =~ s/
            ($JSDOC_COMMENT\s*
            $classname\.prototype\.\w+)
            \s*=\s*
            $fname\s*[\n;]
            /$1 = function(){}/gx;
    
        $inner_src =~ s/
            ($classname\.prototype\.\w+)
            \s*=\s*
            $fname\s*[\n;]
            /$doc\n$1 = function(){}/gx;
    }
    "{}\n$inner_src";
}

#
# Deconstruct mozilla's __defineGetter__ and __defineSetter__
# (Yes, I know this goes against my principles...)
#
sub deconstruct_getset {
    my ($src) = @_;
    # Crack open the assignments for define(Getter|Setter)
    my $crack = sub { 
        my $code = shift; $code =~ s/^.(.*).$/$1/s;
        my ($name) = split ",", $code; 
        $name = ($name =~ /.*?(['"])([^'"]+)\1/)[1];
        $name;
    };
    for my $prefix ('get', 'set'){
        my $fname = "define\u${prefix}ter";
        $src =~ s/
            (\w+(?:\.\w+)*
            \.prototype)
            \.__${fname}__\s*($BAL_PAREN)/
            my $name = $crack->($2);
            "$1.$name = null"/gex;
    }
    $src;
}


#
# Add an annotation (@tag) to a documentation block. The block is assumed to
# be a valid JSDoc documentation block ==> /^\/\*\*.*\*\/$/  
# and no checking is done to verify this
#
sub annotate_comment {
    my $annotation = $_[1] || '@private'; 
    return "\n/** $annotation */" unless $_[0];
    substr($_[0], 0, -2) . " \n$annotation \n*/";
}

#
# This is here to stop perl from segfaulting from deep recursion in regexes
# The first character in the text _should_ be open_token, as everything
# before open_token will be discarded, unless there is no matching text 
# at all. 
#
sub find_balanced_block {
    my ($open_token, $close_token, $text) = @_;
    my ($count, $open, $close) = (0, 0, 0);
    return ('', $text) unless $text =~ /\Q$open_token\E/;
    $text =~ s/^.*?(?=\Q$open_token\E)//s;
    for (split //, $text){
       $count++;
       $open++ if $_ eq $open_token;
       $close++ if $_ eq $close_token;
       last unless ($open != $close);
    }
    warn "Unbalanced block\n" if ($open != $close);
    (substr($text, 0, $count), substr($text, $count)); 
}

#
# Remove the $CONFIG{GLOBALS_NAME} class if it doesn't contain anything
#
sub filter_globals {
    my $global = $CLASSES{$CONFIG{GLOBALS_NAME}};
    delete $CLASSES{$CONFIG{GLOBALS_NAME}} if
        not (defined $global->{constructor_args} ||
                defined $global->{constructor_detail}) 
            && not (@{$global->{instance_methods}} ||
            @{$global->{instance_fields}} ||
            @{$global->{class_methods}} ||
            @{$global->{class_fields}});

    # Also get rid of extra info under the '__files__' key
    delete $CLASSES{__FILES__}->{$_} for qw(constructor_params constructor_args
                                          constructor_detail class_methods
                                          constructor_vars);
}


#
# Try to grab the first block comment from the file that has a @fileoverview
# tag in it, and get the file info from there
#
sub parse_file_info {
    my ($src) = @_;
    my %fileinfo = ( src => $src );
    while ($src =~ /($JSDOC_COMMENT)/g){
       local $_ = substr($1, 3, length($1) - 5); # Get the actual content 
       if (/\@fileoverview\b/){
          my $doc = parse_jsdoc_comment($_, 1);
          %fileinfo = (%{$doc->{vars}}, %fileinfo);
          last;
       }
    }                        
    $CLASSES{__FILES__}->{$CTX_FILE} = \%fileinfo if $CTX_FILE;
}

1;
