#!/usr/bin/perl
#
# $Id$
#
# include.pl - Include file for all the perl scripts that contains reusable functions
#

##############################
# Global Variables
##############################
$db_include	=	"/etc/local.inc";	# Local Include file for database username and password
$tar_dir	=	"/tmp";			# Place to put deleted user's accounts
$uid_add	=	"20000";		# How much to add to the database uid to get the unix uid
$gid_add	=	"1000";			# How much to add to the database gid to get the unix uid
$homedir_prefix =	"/home/users/";		# What prefix to add to the user's homedir
$grpdir_prefix  =	"/home/groups/";	# What prefix to add to the project's homedir
$cvs_prefix     =       "/cvsroot";              # What prefix to add to the cvs root directory
$ftp_frs_dir_prefix  =	"/home/ftp/codex/";	# What prefix to add to the ftp project file release homedir
$ftp_anon_dir_prefix  =	"/home/ftp/pub/";	# What prefix to add to the anon ftp project homedir
$file_dir	=	"/home/dummy/dumps/";	# Where should we stick files we're working with
$dummy_uid      =       "103";                  # UserID of the dummy user that will own group's files
$date           =       int(time()/3600/24);    # Get the number of days since 1/1/1970 for /etc/shadow
$apache_conf    =       "/etc/httpd/conf/httpd.conf"; # Apache configuration file

##############################
# Database Connect Functions
##############################
sub db_connect {
	my ($foo, $bar);
	
	# open up database include file and get the database variables
	open(FILE, $db_include) || die "Can't open $db_include: $!\n";
	while (<FILE>) {
		next if ( /^\s*\/\// );
		($foo, $bar) = split /=/;
		if ($foo) { eval $_ };
	}
	close(FILE);

	# connect to the database
	$dbh ||= DBI->connect("DBI:mysql:$sys_dbname:$sys_dbhost", "$sys_dbuser", "$sys_dbpasswd");
}

##############################
# File open function, spews the entire file to an array.
##############################
sub open_array_file {
        my $filename = shift(@_);
        
        open (FD, $filename) || die "Can't open $filename: $!.\n";
        @tmp_array = <FD>;
        close(FD);
        
        return @tmp_array;
}       

#############################
# File write function.
#############################
sub write_array_file {
        my ($file_name, @file_array) = @_;
        
        open(FD, ">$file_name") || die "Can't open $file_name: $!.\n";
        foreach (@file_array) { 
                if ($_ ne '') { 
                        print FD;
                }       
        }       
        close(FD);
}

    
#############################
# Get CodeX USer from the apache
# configuration file
#############################
sub get_codex_user {

  open(APCONF, $apache_conf) or return;

  while (<APCONF>) {
    return $1 if /^\s*User\s+(.*)\s*/;
  }

}      
