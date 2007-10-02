#!/usr/bin/perl  
#######################################################################
###                  DocmanUploader.pl                              ###
#######################################################################
#                                                                     #
# Script to upload an entire block of folders & files                 #
#  into Codex document manager.                                       #
#                                                                     #
# usage: DocUploader project project_id source_dir id_dest            #
#                                                                     #
# where:                                                              #
#   -project is your project shortname (where you need                # 
#            to upload  documents)                                    #
#   -project_id is your project ID                                    # 
#   -source_dir is the local path to the directory containing         #
#           documents you need to upload                              #
#   -id_destination is the id of the folder (in codex doc             #
#                   manager)                                          #
# NB: You have to specify the path to your codex cli in the line 35   #
#     and the URL of your CodeX server on line 36                     #
# Author : Hamouda LAYOUNI                                            #
# email  : hamouda.layouni@st.com                                     #
#######################################################################

use File::Util;
use WWW::Mechanize;
use Data::Dumper;
use HTTP::Cookies;
use Term::ReadKey;

#################################
#      /path/to/codex/cli       #
#################################

$_cli_dir   = "/prj/sts_sds/layounih/codex_cli-0.3.2";
$_codex_url = "https://codex.xerox.com";
 
# End of parameter section
 
if ($#ARGV != 3) {
    print "usage: DocUploader project project_id source_dir id_destination \n";
    exit;
}



print "Login : ";
ReadMode('normal');
$_username = ReadLine(0);
chomp ($_username);
print "Password for $_username :";
ReadMode('noecho');
$_password = ReadLine(0);
chomp ($_password);
ReadMode('normal');

$_login_url   = $_codex_url."/account/login.php";
$_project     = $ARGV[0];
$_project_id  = $ARGV[1];
$_source      = $ARGV[2];
$_id_dest     = $ARGV[3];

  
my @source_table;
my($f1) = File::Util->new();
my($f2) = File::Util->new();


$_source_dir  =  $_source;                

@level = split(/\//, $_source_dir);
$_initial_length=@level;

my(@dirs) = $f1->list_dir($_source_dir,'--recurse','--dirs-only');

my(@files) = $f2->list_dir($_source_dir,'--recurse','--files-only');   


$i=0;

###############################################################
##    saving the structure of the source in an array         ##
##    to be used after to create items into codex docman     ##
###############################################################

for $item (@dirs)
{

    $_last_length = $_initial_length;
    
    
    @level = split(/\//, $item);
    
    $length = @level;
    
    if ($length == $_last_length)
    {
        $position = $length - $_initial_length;
	$source_table[$i][0]= @level[$length-1]; #item
	$source_table[$i][1]= $position;         #position
	$source_table[$i][2]= @level[$length-2]; #parent_item
	$source_table[$i][3]= "folder";          #type
	$i=$i+1;
	
    }
    else
    {
        $_last_length=$_last_length+1;
	$position = $length - $_initial_length;
	$source_table[$i][0]= @level[$length-1];  #item
	$source_table[$i][1]= $position;          #position
	$source_table[$i][2]= @level[$length-2];  #parent_item
	$source_table[$i][3]= "folder";           #type
	$i=$i+1;
	
    }
   
}


for $item (@files)
{

    $_last_length = $_initial_length;
    
    
    @level = split(/\//, $item);
    
    $length = @level;
    
    if ($length == $_last_length)
    {
        $position = $length - $_initial_length;
	$source_table[$i][0]= @level[$length-1]; #item
	$source_table[$i][1]= $position;          #position
	$source_table[$i][2]= @level[$length-2]; #parent_item
	$source_table[$i][3]= "file";             #type
	$source_table[$i][4]= $item;              #path to file
	$i=$i+1;
    }
    else
    {
        $_last_length=$_last_length+1;
	$position = $length - $_initial_length;
	$source_table[$i][0]= @level[$length-1]; #item
	$source_table[$i][1]= $position;          #position
	$source_table[$i][2]= @level[$length-2]; #parent_item
	$source_table[$i][3]= "file";             #type
	$source_table[$i][4]= $item;              #path to file
	$i=$i+1;
	
    }
   
}

###########################################
##             LOGIN TO CODEX            ##
###########################################

print "\n....... logging in to codex .......\n\n\n\n";

$_login_cmd="$_cli_dir"."/"."./codex.php login --username="."$_username"." --password="."$_password";
system ("$_login_cmd");



$_stay_in_ssl = 1;                     # item of the login form
	$_login_var = 'Login';                 # item of the login form
	
	
	$bot = WWW::Mechanize->new;
$bot->get($_login_url);
$bot->form_number(0);
 
#------- saving cookies --------# 

$bot->cookie_jar(
      HTTP::Cookies->new(
          file           => "$ENV{HOME}/.DocUploderCookies",
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
##         LOGGED IN TO CODEX            ##
###########################################

#----------------------------------------------------------------------------#

############################################################################
##             Copying the documents structure in Codex Docman            ##
############################################################################ 

$_depth = 0;

for $i (0..$#source_table)
{
    if ($source_table[$i][1] > $_depth )
    {
	$_depth = $source_table[$i][1];
    }
}


for $i (0..$#source_table)
{
    for $j (1..$_depth)
    {
	if ($source_table[$i][1] == $j)
	{
		
		if ($source_table[$i][3] eq "folder")
		{
		    if ($source_table[$i][1] == 1)
		    {
		        
			########################################################
			##                  create  1st folders               ##
			########################################################
						
			print "\nCreating $source_table[$i][0] folder ....."; 
      $_create_folder_cmd="$_cli_dir"."/"."./codex.php docman createFolder --project="."$_project"." --parent_id="."$_id_dest"." --title=\""."$source_table[$i][0]"."\"" ;
			$_cmd_output=`$_create_folder_cmd`;
			
			###################################################
			##           save  id of created folder          ##
			###################################################
						
			## get id of created folder here 
			
			($JUNK,$JUNK,$JUNK,$tmp) = split(/\n/, $_cmd_output);
			($JUNK,$_id_created_folder) = split(/ /, $tmp);
			$source_table[$i][4] = $_id_created_folder;
			
		    }
		    else
		    {
		  ###################################################
			##               find parent id                  ##
			###################################################
			
			$_parent_position = $source_table[$i][1] - 1; 
			
			for ($k=0; $k < @source_table; $k++) 
			{
			    if (($source_table[$k][0] eq $source_table[$i][2]) && ($source_table[$k][1] ==  $_parent_position )) 
			    {
                                $_parent_id = $source_table[$k][4];
                            }
                        }
			
			###################################################
			##                 create  folders               ##
			###################################################
		        
			
			print "\nCreating $source_table[$i][0] folder .....";
      
      $_create_folder_cmd="$_cli_dir"."/"."./codex.php docman createFolder --project="."$_project"." --parent_id="."$_parent_id"." --title=\""."$source_table[$i][0]"."\"" ;
	
			$_cmd_output=`$_create_folder_cmd`;
			
			###################################################
			##           save  id of created folder          ##
			###################################################
						
			## get id of created folder here 
			
			($JUNK,$JUNK,$JUNK,$tmp) = split(/\n/, $_cmd_output);
			($JUNK,$_id_created_folder) = split(/ /, $tmp);
			$source_table[$i][4] = $_id_created_folder;
			
		    
		    }
		}
		else
		{
		    if ($source_table[$i][1] == 1)
		    {
			
			###########################################################
			##                  create  1st files                    ##
			###########################################################
				
		     				
			
			    $_upload_document_url = $_codex_url."/plugins/docman/?group_id="."$_project_id"."&action=newDocument&id="."$_id_dest"."&bc=1";  
			
			    
			    
			    print "\nUploading $source_table[$i][0] file ......."; 
	
        $bot->get($_upload_document_url);
        $bot->form_number(2);

	        $titleform = 'item[title]';
	        $bot->field( $titleform => $source_table[$i][0] );
	        $bot->field( file => $source_table[$i][4] );
	        $bot->click();
			 			
		    }
		    else
		    {
		
      ###################################################
			##               find parent id                  ##
			###################################################
			
			$_file_parent_position = $source_table[$i][1] - 1; 
			
			for ($l=0; $l < @source_table; $l++) 
			{
			    if (($source_table[$l][0] eq $source_table[$i][2]) && ($source_table[$l][1] ==  $_file_parent_position )) 
			    {
                                $_parent_id = $source_table[$l][4];
                            }
                        }
			
			######################################################
			##                 create  files                    ##
			######################################################
		        
			
			    $_upload_document_url = $_codex_url."/plugins/docman/?group_id="."$_project_id"."&action=newDocument&id="."$_parent_id"."&bc=1";  
			
			    
			    
			    print "\nUploading $source_table[$i][0] file ......."; 
	
        $bot->get($_upload_document_url);
        $bot->form_number(2);

	        $titleform = 'item[title]';
	        $bot->field( $titleform => $source_table[$i][0] );
	        $bot->field( file => $source_table[$i][4] );
	        $bot->click();
			
	    
		    }
		}
	}
    }    
}



#############################################
##            LOGOUT FROM CODEX            ##
#############################################

print "\n\n\n\n........ logging out from Codex ........\n\n\n\n";
$_logout_cmd="$_cli_dir"."/"."./codex.php logout";
  
system ("$_logout_cmd");
