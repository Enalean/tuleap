#!/usr/bin/perl
#
# 
#
use DBI;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions

&db_connect;

#
# Is current server master ?
#
my $server_is_master = is_current_server_master();

#
# grab Table information
#
my $query = "SELECT unix_group_name,group_name,location,server_id FROM groups g, service s WHERE g.status = 'A' AND s.group_id = g.group_id AND s.short_name = 'svn'";
my $c = $dbh->prepare($query);
$c->execute();

my $warn_noip=0;

while(my ($unix_group_name,$group_name,$location,$server_id) = $c->fetchrow()) {

    # Test if svn service for current project is located on this server
    if(service_available_on_server($server_is_master, $location, $server_id)) {
	push @subversion_dir_zone,
	( "<Location /svnroot/$unix_group_name>\n",
	  "    DAV svn\n",
	  "    SVNPath $svn_prefix/$unix_group_name\n",
	  "    AuthzSVNAccessFile $svn_prefix/$unix_group_name/.SVNAccessFile\n",
	  "    Require valid-user\n",
	  "    AuthType Basic\n",
	  "    AuthName \"Subversion Authorization ($group_name)\"\n",
	  "    AuthUserFile $apache_htpasswd\n",
          "    SVNIndexXSLT \"/svn/repos-web/view/repos.xsl\"\n",
	  "</Location>\n\n");
    }
}

write_array_file("$dump_dir/subversion_dir_dump", @subversion_dir_zone);
