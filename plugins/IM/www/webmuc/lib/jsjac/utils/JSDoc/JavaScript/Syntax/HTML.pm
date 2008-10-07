package JavaScript::Syntax::HTML;

=head1 NAME

JavaScript::Syntax::HTML - Convert JavaScript sourcecode to HTML

=head1 SYNOPSIS

    use JavaScript::Syntax::HTML qw(to_html to_html_document);
    my $html_fragment = to_html($js_src);
    my $html_doc = output_html_document($js_src);

=head1 DESCRIPTION

JavaScript::Syntax::HTML processes JavaScript code and outputs HTML with
all reserved words marked up. 

The to_html method only outputs an HTML fragment (no <body> or <html> tags),
and only marks up the reserved words with CSS styles.

The to_html_document method outputs a full HTML document, and does include
style information for the reserved words markup.

The style classes that can be defined for use with to_html are C<comment>, 
C<literal>, and C<reserved>.

=head1 AUTHOR

Gabriel Reid gab_reid@users.sourceforge.net

=cut

use warnings;
use strict;
use Exporter;

our @ISA = qw(Exporter);
our @EXPORT_OK = qw(to_html to_html_document);

sub to_html {
    local $_ = shift;
    s/\&/&amp;/g;
    s/</&lt;/g;
    s/>/&gt;/g;
    s/
        ((?:\/\*.*?\*\/)
        |
        (?:\/\/[^\n]*$))
        |
        ('[^']*'|"[^"]*")
        |
        \b(function|for|if|while|return|else|prototype|this)\b
        / get_substitution($1, $2, $3) /egsxm; 
    $_;
}

sub get_substitution {
    my ($comment, $stringliteral, $resword) = @_;
    my $content;
    if ($comment){
        $comment =~ s/(\@\w+)\b/get_span('attrib', $1)/eg;
        return get_span('comment', $comment);
    } elsif ($stringliteral){
        return get_span('literal', $stringliteral);
    } elsif ($resword){
        return get_span('reserved', $resword);
    }
}

sub get_span {
    my ($class, $inner) = @_;
    qq(<span class="$class">$inner</span>);
}

sub to_html_document {
    my ($src) = @_;
    $src = &to_html($src);
    qq(
<html>
    <head>
	<style>
            body { background: #FFFFFF }
	    .reserved { color: #DD3333 }
            .comment { color: #339933 }
            .attrib { color: #FF0000 }
            .literal { color: #5555FF }
	</style>
    </head>
    <body>
	<pre>
$src
	</pre>
    </body>
</html>);
}
