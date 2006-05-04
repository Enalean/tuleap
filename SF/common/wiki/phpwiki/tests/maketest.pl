#!/usr/bin/perl

# $Id: maketest.pl 1422 2005-04-12 13:33:49Z guerin $

# read in a file, generate Java code to run a test.
# Steve Wainstead, March 2001.

# Naturally, this is not a recursive descent parser (sorry to disappoint you)
# but a fairly cut and dry script relying on "if" clauses to parse the input
# files. It was the shortest route to the answer for now, though certainly not
# the best one.

use constant BASEURL => 'http://127.0.0.1/~swain/projects/phpwiki-1.3.x/';

die <<"EOLN" unless $ARGV[0];

Usage: $0 <inputfile0> [ <inputfile1> <inputfile2> ... <inputfileN> ]
    where 'inputfile' is the name of a configuration file that specifies
    the form fields and values. The name of the file should be similar to
    ClassName.inputs, and this script will produce a Java file called
    ClassName.java. 
EOLN

#print "passed in: ", join(" ", @ARGV), "\n";

my ($start_of_file, $end_of_file);

# read in the skeleton file from this script below the END tag
while (<DATA>) {
    last if /PRINT_TEST_CODE/;
    $start_of_file .= $_;
}

while (<DATA>) {
    $end_of_file .= $_;
}
    

while ($inputfile = shift(@ARGV)) {
    
    $inputfile =~ /(\w+)\.inputs$/;
    my $classname = $1 || 'Test';
    $start_of_file =~ s/__CLASSNAME__/$classname/g;
    $end_of_file    =~ s/__CLASSNAME__/$classname/g;
    
    open OUTFILE, ">${classname}.java" 
        or die "Can't open '${classname}.java' for writing: $!\n";
    
    
    # start each new file with a timestamp and user name
    print OUTFILE "// This file was automatically generated on ", 
        scalar localtime(), " by $ENV{USER}\n";
    

    print OUTFILE $start_of_file;

    # read in the file in chunks with "go\n" as the record separator
    local($/) = "go\n";

    open FILE, "<$inputfile" 
        or do {
           close OUTFILE;
           unlink "${classname}.java";
           die "Can't read config file '$inputfile': $!\n";
        };
    

    # set the response message/output... this is output after each "go" block
    # is run.
    $response = <<"EOLN";
    
                //System.out.println( "Here's the metadata for the response:" );
                //System.out.println( "URL: " + response.getURL() );
                //System.out.println( "Page title: " + response.getTitle() );
                //System.out.println( "Response code: " + response.getResponseCode() );
                //System.out.println( response );
                //System.out.println( transaction_boundary );
    
EOLN


    # here is where we do the "parsing" of the .inputs file.
    while ($block = <FILE>) {
    
        next unless $block =~ /go$/;
        
        if ($block =~ /type:\s*starting_page/) {
            starting_page($block);
        } elsif ($block =~ /type:\s*fill_and_submit_form/) {
            fill_and_submit_form($block);
        } elsif ($block =~ /type:\s*follow_link/) {
            follow_link($block);
        } elsif ($block =~ /type:\s*follow_image_link/) {
            follow_image_link($block);
        } else {
        # error
            die "This block does not match any known action:\n$block\n";
        }
        
    }
    
    print OUTFILE $end_of_file;

}    



# End of main... subs are next, followed by the boilerplate code for the 
# java files after the END thingie. What is that thingie called?

sub starting_page {
    my $block = shift;
    $block =~ m/start_url:\s*(http.*?)$/m;
    my $start_url = BASEURL . $1;
    my $assertions = &get_assertions($block);

    print OUTFILE <<"EOLN"

            System.out.println( "Name for this test: " + dealname );

            // make a request object for the conversation object
            try {
                myurl = "$start_url";
                request = new GetMethodWebRequest( myurl );
                response = conversation.getResponse( request );
            } catch (Exception e) {
                throw new Exception("Couldn't get a page from URL '$start_url'\\n" + e);
            }
            $assertions
            $response

EOLN
}

sub follow_link {
    my $block = shift;
    $block =~ m/follow_link:\s*(".*?")$/m;
    my $link_text = $1;
    my $assertions = &get_assertions($block);

    print OUTFILE <<"EOLN";

            // follow a plain link with text '$link_text'
            linkname = $link_text;
            link_to_follow = response.getLinkWith(linkname);

            if (link_to_follow == null)
                throw new Exception("The link '" + linkname + "' was not found.");

            request = link_to_follow.getRequest();
            System.out.println( request );

            try {
                response = conversation.getResponse( request );
            } catch (Exception r) {
                throw new Exception(r + "\\nCouldn't follow the link!\\n" +
                                    "Request was:\\n" + request + "\\n");
            }
            $assertions
            $response

EOLN

}

sub follow_image_link {
    my $block = shift;
    $block =~ m/follow_image_link:\s*(".*?")$/m;
    my $link_name = $1;
    my $assertions = &get_assertions($block);

    print OUTFILE <<"EOLN";

            // follow an image link with text '$link_name'
            linkname = $link_name;
            link_to_follow = response.getLinkWithImageText(linkname);

            if (link_to_follow == null)
                throw new Exception("The link '" + linkname + "' was not found.");

            request = link_to_follow.getRequest();
            System.out.println( request );

            try {
                response = conversation.getResponse( request );
            } catch (Exception r) {
                throw new Exception(r + "\\nCouldn't follow the image link!\\n" +
                                    "Request was:\\n" + request + "\\n");
            }
            $assertions
            $response

EOLN

}

sub fill_and_submit_form {
    my $block = shift;
    @lines = make_array_from_block($block);

    my ($form_num, $submit_num, $form_name, $submit_name);
    my $requests = "\n";
    my $assertions = &get_assertions($block);

    for (@lines) { 
        if ( /form_num:\s*(\d)/ ) {
            $form_num = $1;
        } elsif ( /submitbutton_num:\s*(\d)/ ) {
            $submit_num = $1;
        } elsif ( /form_name:\s*(\w+)/ ) {
            $form_name = $1;
            #print "form name: '$form_name'\n";
        } elsif ( /submitbutton_name:\s*(\w+)/ ) {
            $submit_name = $1;
            #print "submit name: '$submit_name'\n";
        } elsif ( /setparam:\s*(.+)$/ ) {
            $requests .= "            request.setParameter($1);\n";
        }
    }
    unless ( (defined $form_num || defined $form_name)
            && (defined $submit_num || defined $submit_name)
            && defined $requests) {
        die <<"        EOLN";

            Missing variable:
            form_num: '$form_num' (you need either form_num or form_name)
            submit_num: '$submit_num' (you need either submit_num or submit_name)
            form_name: '$form_name'
            submit_name: '$submit_name'
            requests: '$requests'
        EOLN
    }

    # provide a bit of minor error detection...
    if ( defined $form_num && defined $form_name) {
        die <<"        EOLN";

        You can't have both a form number and a form name defined
        (got form_num '$form_num' and form_name '$form_name' for
        block:\n$block
        EOLN
    }

    if ( defined $submit_num && defined $submit_name) {
        die <<"        EOLN";

        You can't have both a submit button number and a 
        submit button name defined
        (got submit_num '$submit_num' and submit_name '$submit_name' for
        block:\n$block
        EOLN
    }

    if (defined $form_num) {
        $form_code = <<"EOLN";

            htmlforms = response.getForms();

            if (htmlforms == null || htmlforms.length == 0)
                throw new Exception("No HTML form found for:\\n" + response);

            htmlform = htmlforms[$form_num];
EOLN
    } elsif (defined $form_name) {
        $form_code = <<"EOLN";

            htmlform = response.getFormWithName("$form_name");
            if (htmlform == null)
                throw new Exception("No HTML form named '$form_name' found for:\\n" 
                                   + response);
EOLN

    } else {
        # error
        die "Didn't get a form_name or form_num for this block:\n$block\n";
    }


    if (defined $submit_num) {
        $submit_button_code = <<"EOLN";

            submitButtonArray = htmlform.getSubmitButtons();

            if (submitButtonArray == null || submitButtonArray.length == 0)
                throw new Exception("Didn't get a submit button array in "
                                    + "response object:\\n");


            request = htmlform.getRequest(submitButtonArray[$submit_num]);
EOLN
    } elsif (defined $submit_name) {
        $submit_button_code = <<"EOLN";

            submitbutton = htmlform.getSubmitButton("$submit_name");
            if (submitbutton == null)
                throw new Exception("Couldn't fine a submit button "
                                  + "named '$submit_name'\\n"
                                  + response);
            request = htmlform.getRequest(submitbutton);

EOLN
    } else {
        # error
        die "Didn't get a submit_num or submit_name for this block:\n$block\n";
    }
        
    print OUTFILE <<"    EOLN";

            // get and fill the HTML form
            $form_code
            $submit_button_code
            try {
                $requests
            } catch (Exception n) {
                throw new Exception( n
                                     + "\\nCouldn't set a parameter in this request:\\n"
                                     + request );
            }

            try {
                response = conversation.getResponse( request );
            } catch (Exception r) {
                throw new Exception(r + "\\nCouldn't submit the form!\\n" +
                                    "Request was:\\n" + request + "\\n");
            }
            $assertions
            $response

    EOLN

}


sub get_assertions {
    my $block = shift;
    my $return_text = &assert_url($block);
    $return_text .=   &assert_title($block);
    $return_text .=   &assert_field($block);
    $return_text .=   &assert_text($block);
    #print "Return text:\n$return_text\n";
    return $return_text;
}

sub assert_url {
 
    my $block = shift;
    return unless $block =~ /^assert_url:\s*(.*?)$/m;

    my $url = $1;
    
    return <<"EOLN";

            // assert the URL
            if ( response.getURL().toString().indexOf( "$url" ) != -1)
                System.out.println("\tURL match: matched '$url' OK");
            else
                throw new Exception ("URL match: Didn't match URL '$url' ERROR");
EOLN
}

# assert that the title of the page is correct
sub assert_title {

    my $block = shift;
    return unless $block =~ /^assert_title:\s*(.*?)$/m;

    my $title = $1;

    return <<"EOLN";

            // assert the page title
            if ( response.getTitle().toString().indexOf( "$title" ) != -1)
                System.out.println("\tTitle match: Matched '$title' OK");
            else
                throw new Exception("Title match: did not match title '$title' ERROR");
EOLN
}

# assert that a form field matches a value
sub assert_field {

    my $block = shift;
    return unless $block =~ /assert_field/m;

    my @lines = &make_array_from_block($block);
    my $return_text = "";

    foreach my $line (@lines) {

        if ($line =~ /^assert_field:\s*(\d|"\w+")\s*("[^"]+")\s*("[^"]+")\s*$/) {
            my $form   = $1;
            my $field_name = $2;
            my $field_val  = $3;

            if ($form =~ /^\d+$/) {
                $getform_string = "response.getForms()[$form].getParameterValue($field_name)";
            } else {
                $getform_string = "response.getFormWithName($form).getParameterValue($field_name)";
            }

            #print "$getform_string\n";

            $return_text .= <<"EOLN";

            // assertion: form '$form', field '$field_name' == '$field_val'
            if ( ${field_val}.equals($getform_string) )
                System.out.println("\tField match: '" + $field_name + "' held '" + $field_val + "' OK");
            else
                throw new Exception ("Field match: Field '" + $field_name + "' didn't match '" + $field_val + "' ERROR");
EOLN


        }
    }

    return $return_text;
}


sub assert_text {
    my $block = shift;
    return unless $block =~ /assert_text/m;

    my @lines = &make_array_from_block($block);
    my $return_text = "";

    foreach my $line (@lines) {
        if ($line =~ /^assert_text:\s*(.*)$/) {
            my $search_string = $1;

            $return_text .= <<"EOLN";

            // find the text string '$search_string' in the page source
            if ( response.getText().indexOf( "$search_string" ) != -1)
                System.out.println( "I found the text '$search_string' in the page OK" );
            else 
                throw new Exception( "Couldn't find text: '$search_string'" );

EOLN

    return $return_text;

        }
    }
}

sub make_array_from_block {
    my $block = shift;
    my @return_array;
    my @lines = split /\n/, $block;
    foreach $line (@lines) {
        next if $line =~ /^#/;
        next unless $line =~ /\w/;
        next if $line =~ /^go/;
        push @return_array, $line;
    }
    return @return_array;
}


__END__
import com.meterware.httpunit.*;

import java.io.IOException;
import java.net.MalformedURLException;
import java.util.*;
import java.text.*;

import org.xml.sax.*;

public class __CLASSNAME__ {

    public static void main( String[] params ) {

        boolean             success = true;

        try {
            WebRequest          request;
            WebResponse         response;
            WebConversation     conversation = new WebConversation();
            WebForm[]           htmlforms;
            WebForm             htmlform;
            SubmitButton[]      submitButtonArray;
            SubmitButton        submitbutton;
            WebLink             link_to_follow;
            String              myurl;
            String              linkname;
            String              dealname = makeUniqueDealName("__CLASSNAME__ Test");
            String              transaction_boundary = "This is a transaction boundary.";

            /* PRINT_TEST_CODE */

        } catch (Exception e) {
            System.err.println( "Exception: " + e );
            success = false;
        } finally {
            if (success == true) {
                System.out.println( "__CLASSNAME__ test successful." );
            } else {
                System.out.println( "__CLASSNAME__ test failed." );
            }

        }
    }


    public static String makeUniqueDealName(String dealname) {
        Date today = new Date();
        DateFormat df = DateFormat.getDateTimeInstance(DateFormat.SHORT,
                                                       DateFormat.MEDIUM);
        return dealname + " " + df.format(today);
    }

}

