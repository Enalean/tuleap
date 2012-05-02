package JSDoc::XML;

use strict;
use warnings;
use HTML::Template;

sub new {
    my ($package, $location) = @_;
    bless { location => "${location}JSDoc/" }, $package;
}

sub output {
    my ($self, $classes) = @_;
    my @classes = _preprocess(
        grep {defined($_->{classname})} values %$classes);
    my $template = HTML::Template->new(
        filename    => $self->{location} . 'xml.tmpl',
        die_on_bad_params => 1);

    $template->param(classes => \@classes);
    return $template->output;
}

sub _preprocess {
    my @classes = @_;
    for (@classes){
        $_->{inherits} = _preprocess_inherits($_->{inherits});
        $_->{constructor_vars} = _preprocess_vars($_->{constructor_vars});
        for my $method (@{$_->{instance_methods}}, @{$_->{class_methods}}){
            $method->{vars} = _preprocess_vars($method->{vars});
        }
        for my $field (@{$_->{instance_fields}}, @{$_->{class_fields}}){
            $field->{field_vars} = _preprocess_vars($field->{field_vars});
        }
    }
    @classes;
}

sub _preprocess_inherits {
    my ($inherits) = @_;
    my @inherits;
    for my $class (keys %$inherits){
        my $inherit = {
            class   => $class,
            methods => [map { name => $_ }, 
                            @{$inherits->{$class}->{instance_methods}}]}; 
        push @inherits, $inherit;
    }
    \@inherits;
}

sub _preprocess_vars {
    my ($vars) = @_;
    return $vars if ref($vars) eq 'ARRAY'; 
    my @vars;
    for my $key (keys %$vars){
        my $var;
        if (ref($vars->{$key}) eq 'ARRAY'){
            $var = { 
                '@name' => $key,
                values  => [map { val => $_ }, @{$vars->{$key}}] };
        } else {
            $var = {
                '@name' => $key,
                values  => [ { val => $vars->{$key} } ] };
        }
        push @vars, $var;
    }
    \@vars;
}

1;
