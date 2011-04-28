package JSDoc::XMI;

use strict;
use warnings;
use HTML::Template;
use Data::Dumper;

=head1 DESCRIPTION

    @packages    

        @classes
            $classname
            $classid
            $classuuid
            $classvisibility (public|protected|private)
           
            @specializationids
                $specializationid

            $generalizationid

            @attributes
                $attributeid
                $attributeuuid
                $attributename
                $attributevisibility (public|protected|private)
                $ownerscope (instance|classifier)
                $classid
                $typeid

            @methods
                $methodid
                $methoduuid
                $methodname
                $methodvisibility (public|protected|private)
                $ownerscope (instance|classifier)
                $returnid
                $returnuuid
                $returntypeid

    @datatypes
        $datatypeid
        $datatypeuuid
        $datatypename

    @generalizations
        $generalizationid
        $generalizationuuid
        $generalizationchild
        $generalizationparent

=cut

sub new {
    my ($package, $location) = @_;
    bless { 
        location        => "${location}JSDoc/",
        idcounter       => 2,
        types           => {},
        classes         => {},
        generalizations => {} 
    }, $package;
}

sub output {
    my ($self, $classes) = @_;
    
    my $template = HTML::Template->new(
        filename    => $self->{location} . 'xmi.tmpl',
        die_on_bad_params => 1);

    my @packages = $self->get_packages($classes);
    my @datatypes = $self->get_datatypes;
    my @generalizations = $self->get_generalizations;
    
    $template->param(
        packages        => \@packages, 
        datatypes       => \@datatypes,
        generalizations => \@generalizations );
    return $template->output;
}

sub get_id {
    'xmi.' . shift->{idcounter}++;
}

sub get_uuid {
    my @chars = ('A'..'Z', 'a'..'z', 0..9);
    my @uuid;
    for (1..32){
        push @uuid, $chars[rand(@chars)];
    }
    join("", @uuid);
}

sub get_packages {
    my ($self, $classes) = @_;
    my %packages;
    push(@{$packages{$_->{package}}}, $_) 
        for $self->get_classes($classes);
    map { 
        name        => $_, 
        classes     => $packages{$_},
        packageid   => $self->get_id,
        packageuuid => $self->get_uuid 
    }, keys %packages;
}

sub get_classes {
    my ($self, $classes) = @_;
    my @classes;

    # Store all the class-ids before we start on anything else
    $self->add_class($_) for keys %$classes;

    for my $cname (keys %$classes){
        my $class = { 
            classname       => $cname,
            classid         => $self->add_class($cname),
            classuuid       => $self->get_uuid,
            classvisibility => 
                defined($classes->{$cname}->{constructor_vars}->{private})
                ? 'private' : 'public'
        };
        $class->{attributes} = $self->get_attributes(
            $class->{classid},
            $classes->{$cname});

        $class->{methods} = $self->get_methods(
            $class->{classid},
            $classes->{$cname});

        $class->{generalizationid} = 
            $self->get_generalizationid($classes->{$cname});

        $class->{package} = 
            defined($classes->{$cname}->{constructor_vars}->{package})
            ? $classes->{$cname}->{constructor_vars}->{package}->[0] : '';

        push @classes, $class;
    }
    
    for my $class (@classes){
        $class->{specializationids} = $self->get_specializationids($class);
    }

    @classes;
}

sub get_methods {
    my ($self, $classid, $class) = @_;
    my @methods;

    for my $k (qw(instance class)){
        for my $method (@{$class->{"${k}_methods"}}){
            my $type = defined($method->{vars}->{type})
                ? $method->{vars}->{type}->[0] : 'Object';
            my $meth = {
                methodid            => $self->get_id,
                methoduuid          => $self->get_uuid,
                methodname          => $method->{mapped_name},
                methodvisibility    => 
                    defined($method->{vars}->{private})
                    ? 'private' : 'public',
                ownerscope          => 
                    $k eq 'class' ? 'classifier' : 'instance',
                returnid            => $self->get_id,
                returnuuid          => $self->get_uuid,
                returntypeid        => $self->add_type($type)
            };
            push @methods, $meth;
        }
    }
    return \@methods;
}

sub get_attributes {
    my ($self, $classid, $class) = @_;
    my @attributes;
    for my $k (qw(instance class)){
        for my $field (@{$class->{"${k}_fields"}}){
            my $type = defined($field->{field_vars}->{type})
                ? $field->{field_vars}->{type}->[0] : 'Object';
            my $attr = {
                attributeid         => $self->get_id,
                attributeuuid       => $self->get_uuid,
                attributename       => $field->{field_name},
                attributevisibility => 
                    defined($field->{field_vars}->{private}) 
                    ? 'private' : 'public',
                ownerscope          =>
                    $k eq 'class' ? 'classifier' : 'instance',
                classid             => $classid,
                typeid              => $self->add_type($type)
            };
            push @attributes, $attr;
        }
    }
    \@attributes;  
}

sub get_generalizationid {
    my ($self, $class) = @_;

    if ($class->{extends}){
        return $self->add_generalization(
            $class->{classname},
            $class->{extends});
    }
    '';
}

sub get_specializationids {
    my ($self, $class) = @_;
    my $cname = $class->{classname};
    my $cid = $self->add_class($cname);

    my @specializationids;

    for my $id (keys %{$self->{generalizations}}){
        my $generalization = $self->{generalizations}->{$id};
        if ($generalization->{parent} eq $cid){
            push @specializationids, { specializationid => $id };
        }
    }
    \@specializationids;
}

sub get_datatypes {
    my ($self) = @_;
    my @datatypes;

    while (my ($type, $id) = each(%{$self->{types}})){
        push @datatypes, {
            datatypeid      => $id,
            datatypeuuid    => $self->get_uuid,
            datatypename    => $type };
    }
    @datatypes;
}

sub get_generalizations {
    my ($self) = @_;
    my @generalizations;
    
    while (my ($id, $generalization) = each(%{$self->{generalizations}})){
        push @generalizations, {
            generalizationid        => $id,
            generalizationuuid      => $self->get_uuid,
            generalizationchild     => $generalization->{parent},
            generalizationparent    => $generalization->{child}};
    }
    @generalizations;
}

sub add_type {
    my ($self, $type) = @_;
    $type =~ s/^\s*(\S+)\s*$/$1/;
    if (defined($self->{classes}->{$type})){
        return $self->add_class($type);
    } elsif (defined($self->{types}->{$type})){
        return $self->{types}->{$type};
    } 
    $self->{types}->{$type} = $self->get_id;
}

sub add_class {
    my ($self, $class) = @_;
    $class =~ s/^\s*(\S+)\s*$/$1/;
    if (defined($self->{classes}->{$class})){
        return $self->{classes}->{$class};
    }
    $self->{classes}->{$class} = $self->get_id;
}

sub add_generalization {
    my ($self, $subclassname, $superclassname) = @_;
    my $subclassid = $self->add_class($subclassname);
    my $superclassid = $self->add_class($superclassname);

    my $generalization = {
        child       => $subclassid,
        parent      => $superclassid };

    my $generalizationid = $self->get_id;
    $self->{generalizations}->{$generalizationid} = $generalization;
    $generalizationid;
}

1;
