#!/usr/bin/perl
#
# 
#
# include.pl - Include file for all the perl scripts that contains reusable functions
#

##############################
# Global Variables
##############################
$db_include	=	$ENV{'CODEX_LOCAL_INC'} || "/etc/codex/conf/local.inc"; # Local Include file for database username and password
$uid_add	=	"20000";		# How much to add to the database uid to get the unix uid
$gid_add	=	"1000";			# How much to add to the database gid to get the unix uid
$date           =       int(time()/3600/24);    # Get the number of days since 1/1/1970 for /etc/shadow

&load_local_config($db_include);
1;

##############################
# Local Configuration Load
##############################

sub load_local_config {
        my $filename = shift(@_);
	my ($foo, $bar);
	
        if (! $filename) {$filename=$db_include;} # backward compatibility
	# open up database include file and get the database variables
	open(FILE, $filename) || die "Can't open $filename: $!\n";
	while (<FILE>) {
		next if ( /^\s*\/\// );
                # remove trailing comment if any
                s/;\s*\/\/.*//;
		($foo, $bar) = split /=/;
		if ($foo) { eval $_ };
	}
	close(FILE);
}


##############################
# Database Connect Functions
##############################
sub db_connect {

        &load_local_config($db_config_file);
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
# Now use a temporary file first, then rename once the file is fully written.
#############################
sub write_array_file {
        my ($file_name, @file_array) = @_;
        
        open(FD, ">$file_name.codextemp") || die "Can't open $file_name: $!.\n";
        foreach (@file_array) { 
                if ($_ ne '') { 
                        print FD;
                }       
        }       
        close(FD);
        rename "$file_name.codextemp","$file_name" || die "Can't rename $file_name.codextemp to $file_name: $!.\n";
}

    
#############################
# Get CodeX apache user from local.inc
#############################
sub get_codex_user {

  return $sys_http_user;

  #open(APCONF, $apache_conf) or return;
  #while (<APCONF>) {
  #  return $1 if /^\s*User\s+(.*)\s*/;
  #}

}      
