#!/usr/bin/perl
#######################################################################
###                  DocmanUploader.pl                              ###
#######################################################################
#                                                                     #
# Script to upload an entire block of folders & files                 #
#  into Codendi document manager.                                       #
#                                                                     #
# usage: DocUploader project project_id source_dir id_dest            #
#                                                                     #
# where:                                                              #
#   -project is your project shortname (where you need                #
#            to upload  documents)                                    #
#   -project_id is your project ID                                    #
#   -source_dir is the local path to the directory containing         #
#           documents you need to upload                              #
#   -id_destination is the id of the folder (in codendi doc             #
#                   manager)                                          #
# NB: You have to specify the path to your codendi cli in the line 35   #
#     and the URL of your Codendi server on line 36                     #
# Author : Hamouda LAYOUNI                                            #
# email  : hamouda.layouni@st.com                                     #
#######################################################################

use File::Util;
use WWW::Mechanize;
use Data::Dumper;
use HTTP::Cookies;
use Term::ReadKey;
use Crypt::SSLeay;
use warnings;
use strict;
use Env qw($TULEAP_WSDL);

#################################
#      /path/to/codendi/cli       #
#################################

# Where Codendi-CLI tool stands
my $_cli_dir   = "/usr/share/codendi/cli";
# Server URL where all the stuff will be uploaded
my $_codendi_url = "http://codendi.example.com";
# CLI execution command
my $_cli_cmd   = "/usr/bin/php $_cli_dir/tuleap.php";

######                          ######
###### End of parameter section ######
######                          ######

if ($#ARGV != 3) {
    print "usage: DocUploader project project_id source_dir id_destination \n";
    exit;
}

# Change umask to ensure cookie security
umask 0077;

# Gather some inputs
print "Login : ";
ReadMode('normal');
my $_username = ReadLine(0);
chomp ($_username);
print "Password for $_username :";
ReadMode('noecho');
my $_password = ReadLine(0);
chomp ($_password);
ReadMode('normal');

my $_login_url   = $_codendi_url."/account/login.php";
my $_project     = $ARGV[0];
my $_project_id  = $ARGV[1];
my $_source      = $ARGV[2];
my $_id_dest     = $ARGV[3];

my @source_table;
my($f1) = File::Util->new();
my($f2) = File::Util->new();

my $_source_dir  =  $_source;

my @level = split(/\//, $_source_dir);
my $_initial_length=@level;

my(@dirs) = $f1->list_dir($_source_dir,'--recurse','--dirs-only');

my(@files) = $f2->list_dir($_source_dir,'--recurse','--files-only');

my $i=0;

###############################################################
##    saving the structure of the source in an array         ##
##    to be used after to create items into codendi docman     ##
###############################################################
my $_last_length;
for my $item (@dirs)
{

    $_last_length = $_initial_length;
    @level = split(/\//, $item);

    my $length = @level;

    if ($length == $_last_length)
    {
        my $position = $length - $_initial_length;
	$source_table[$i][0]= $level[$length-1]; #item
	$source_table[$i][1]= $position;         #position
	$source_table[$i][2]= $level[$length-2]; #parent_item
	$source_table[$i][3]= "folder";          #type
	$i=$i+1;
    }
    else
    {
        $_last_length=$_last_length+1;
	my $position = $length - $_initial_length;
	$source_table[$i][0]= $level[$length-1];  #item
	$source_table[$i][1]= $position;          #position
	$source_table[$i][2]= $level[$length-2];  #parent_item
	$source_table[$i][3]= "folder";           #type
	$i=$i+1;
    }
}


for my $item (@files)
{

    $_last_length = $_initial_length;
    @level = split(/\//, $item);

    my $length = @level;

    if ($length == $_last_length)
    {
        my $position = $length - $_initial_length;
	$source_table[$i][0]= $level[$length-1]; #item
	$source_table[$i][1]= $position;          #position
	$source_table[$i][2]= $level[$length-2]; #parent_item
	$source_table[$i][3]= "file";             #type
	$source_table[$i][4]= $item;              #path to file
	$i=$i+1;
    }
    else
    {
        $_last_length=$_last_length+1;
	my $position = $length - $_initial_length;
	$source_table[$i][0]= $level[$length-1]; #item
	$source_table[$i][1]= $position;          #position
	$source_table[$i][2]= $level[$length-2]; #parent_item
	$source_table[$i][3]= "file";             #type
	$source_table[$i][4]= $item;              #path to file
	$i=$i+1;
    }
}

###########################################
##             LOGIN TO Tuleap           ##
###########################################

print "\n....... Logging in to tuleap .......\n";

# Set TULEAP_WSDL according to DocmanUploader settings
$TULEAP_WSDL = $_codendi_url."/soap/index.php?wsdl";
my $_login_cmd=$_cli_cmd.' login --username="'.$_username.'" --password="'.$_password.'"';
system ($_login_cmd) == 0
    or die "Login ($_login_cmd) with Tuleap CLI failed: $?";

my $_stay_in_ssl = 1;                     # item of the login form
my $_login_var = 'Login';                 # item of the login form

my $bot = WWW::Mechanize->new();
my $m = $bot->get($_login_url);
if(!$bot->success()) {
    print "Error: Cannot get login page at: ".$_login_url."\n";
    print "Error: ".$m->response->status_line.": ".$m->status()."\n";
    exit 1;
}
$bot->form_number(0);

#------- saving cookies --------#

$bot->cookie_jar(
    HTTP::Cookies->new(
        file           => "$ENV{HOME}/.codendiDocmanUploaderCookies",
        autosave       => 1,
        ignore_discard => 1,  # le cookie devrait  tre effac    la fin
    )
);

$bot->field( form_loginname => $_username );
$bot->field( form_pw => $_password );
$bot->field( stay_in_ssl => $_stay_in_ssl );
$bot->field( login => $_login_var );
$bot->click();

###########################################
##         LOGGED IN TO CODENDI            ##
###########################################

#----------------------------------------------------------------------------#

############################################################################
##             Copying the documents structure in Codendi Docman            ##
############################################################################ 

my $_depth = 0;

for $i (0..$#source_table) {
    if ($source_table[$i][1] > $_depth ) {
        $_depth = $source_table[$i][1];
    }
}

my $JUNK;
my $tmp;
my $_id_created_folder;

for my $i (0..$#source_table) {
    for my $j (1..$_depth) {
        if ($source_table[$i][1] == $j) {
            if ($source_table[$i][3] eq "folder") {
                if ($source_table[$i][1] == 1) {
                    ########################################################
                    ##                  create  1st folders               ##
                    ########################################################

                    print "\nCreating $source_table[$i][0] folder .....";
                    my $_create_folder_cmd="$_cli_cmd docman createFolder --project="."$_project"." --parent_id="."$_id_dest"." --title=\""."$source_table[$i][0]"."\"" ;
                    my $_cmd_output=`$_create_folder_cmd`;

                    ###################################################
                    ##           save  id of created folder          ##
                    ###################################################

                    ## get id of created folder here
                    ($JUNK,$JUNK,$JUNK,$tmp) = split(/\n/, $_cmd_output);
                    ($JUNK,$_id_created_folder) = split(/ /, $tmp);
                    $source_table[$i][4] = $_id_created_folder;
                }
                else {
                    ###################################################
                    ##               find parent id                  ##
                    ###################################################
                    my $_parent_id;
                    my $_parent_position = $source_table[$i][1] - 1;
                    for (my $k=0; $k < @source_table; $k++) {
                        if (($source_table[$k][0] eq $source_table[$i][2]) && ($source_table[$k][1] ==  $_parent_position )) {
                            $_parent_id = $source_table[$k][4];
                        }
                    }

                    ###################################################
                    ##                 create  folders               ##
                    ###################################################

                    print "\nCreating $source_table[$i][0] folder .....";

                    my $_create_folder_cmd="$_cli_cmd docman createFolder --project="."$_project"." --parent_id="."$_parent_id"." --title=\""."$source_table[$i][0]"."\"" ;
                    my $_cmd_output=`$_create_folder_cmd`;

                    ###################################################
                    ##           save  id of created folder          ##
                    ###################################################

                    ## get id of created folder here
                    ($JUNK,$JUNK,$JUNK,$tmp) = split(/\n/, $_cmd_output);
                    ($JUNK,$_id_created_folder) = split(/ /, $tmp);
                    $source_table[$i][4] = $_id_created_folder;
                }
            }
            else {
                if ($source_table[$i][1] == 1) {
                    ###########################################################
                    ##                  create  1st files                    ##
                    ###########################################################

                    my $_upload_document_url = $_codendi_url."/plugins/docman/?group_id="."$_project_id"."&action=newDocument&id="."$_id_dest"."&bc=1";
                    print "\nUploading $source_table[$i][0] file .......";

                    my $resp = $bot->get($_upload_document_url);
                    if(!$bot->success()) {
                        print STDERR "ERROR: cannot create file at $_upload_document_url\n";
                        print STDERR "ERROR: ".$resp->response->status_line.": ".$resp->status()."\n";
                    }
                    $bot->form_number(2);

                    my $titleform = 'item[title]';
                    $bot->field( $titleform => $source_table[$i][0] );
                    $bot->field( file => $source_table[$i][4] );
                    $bot->click();

                }
                else {
                    ###################################################
                    ##               find parent id                  ##
                    ###################################################
                    my $_parent_id;
                    my $_file_parent_position = $source_table[$i][1] - 1;
                    for (my $l=0; $l < @source_table; $l++) {
                        if (($source_table[$l][0] eq $source_table[$i][2]) && ($source_table[$l][1] ==  $_file_parent_position )) {
                            $_parent_id = $source_table[$l][4];
                        }
                    }

                    ######################################################
                    ##                 create  files                    ##
                    ######################################################

                    my $_upload_document_url = $_codendi_url."/plugins/docman/?group_id="."$_project_id"."&action=newDocument&id="."$_parent_id"."&bc=1";

                    print "\nUploading $source_table[$i][0] file .......";

                    my $resp = $bot->get($_upload_document_url);
                    if(!$bot->success()) {
                        print STDERR "ERROR: cannot create file at $_upload_document_url\n";
                        print STDERR "ERROR: ".$resp->response->status_line.": ".$resp->status()."\n";
                    }
                    $bot->form_number(2);

                    my $titleform = 'item[title]';
                    $bot->field( $titleform => $source_table[$i][0] );
                    $bot->field( file => $source_table[$i][4] );
                    $bot->click();
                }
            }
        }
    }
}



#############################################
##            LOGOUT FROM CODENDI            ##
#############################################

print "\n........ Logging out from Codendi ........\n";
my $_logout_cmd="$_cli_cmd logout";

system ("$_logout_cmd");
