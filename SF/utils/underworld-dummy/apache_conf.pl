#!/usr/bin/perl
#
# $Id$
#
use DBI;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions


&db_connect;



#
# grab Table information
#
my $query = "SELECT http_domain,unix_group_name,group_name,unix_box FROM groups WHERE http_domain LIKE '%.%' AND status = 'A'";
my $c = $dbh->prepare($query);
$c->execute();

# Determine Apache version installed
$output = `httpd -v`;
($apache_version) = ($output =~ /version:.*\/(\d)\.\d+\.\d+/);

while(my ($http_domain,$unix_group_name,$group_name,$unix_box) = $c->fetchrow()) {

	($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$unix_box.$sys_default_domain");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);


	# LJ Custom log used to be on a per project basis but is now
	# in one single file
	#    "  CustomLog /home/groups/$unix_group_name/log/combined_log combined\n",
	# if the HTTP domain given in CodeX is a customized one then use it 
	# as the Server name and create an alias for projectname.codex.xerox.com
	# Note: the DNS entry for the customized HTTP domain must exist somewhere
	# in some DNS server in the Corp.
	$http_domain =~ tr/A-Z/a-z/;
	$codex_domain = "$unix_group_name.$sys_default_domain";
	if ($http_domain ne $codex_domain)
	  {
	    $server_name = "  ServerName $http_domain\n";
	    $server_alias = "  ServerAlias $codex_domain\n";
	  }
	else
	  {
	    $server_name = "  ServerName $codex_domain\n";
	    $server_alias = "";
	  }

	if ($apache_version eq "1") {
	  # Apache 1.x syntax
	  push @apache_zone,
	    ( "<VirtualHost $ip>\n",
	      "$server_name",
	      "$server_alias",
	      "  User dummy\n",
	      "  Group $unix_group_name\n",
	      "  DocumentRoot /home/groups/$unix_group_name/htdocs/\n",
	      "  <Directory /home/groups/$unix_group_name/htdocs>\n",
	      "    AllowOverride AuthConfig Limit Options Indexes\n",
	      "  </Directory>\n",
	      "  CustomLog logs/vhosts-access_log combined\n",
	      "  ScriptAlias /cgi-bin/ /home/groups/$unix_group_name/cgi-bin/\n",
	      "</VirtualHost>\n\n");
	} else {
	  # Apache 2.x syntax
	  push @apache_zone,
	    ( "<VirtualHost $ip>\n",
	      "$server_name",
	      "$server_alias",
	      "  SuexecUserGroup dummy $unix_group_name\n",
	      "  DocumentRoot /home/groups/$unix_group_name/htdocs/\n",
	      "  <Directory /home/groups/$unix_group_name/htdocs>\n",
	      "    AllowOverride AuthConfig Limit Options Indexes\n",
	      "  </Directory>\n",
	      "  CustomLog logs/vhosts-access_log combined\n",
	      "  ScriptAlias /cgi-bin/ /home/groups/$unix_group_name/cgi-bin/\n",
	      "</VirtualHost>\n\n");
	}
}

# Retrieve the dummy's home directory
($name,$passwd,$uid,$gid,$quota,$comment,$gcos,$dir,$shell,$expire) = getpwnam("dummy");

write_array_file("$dir/dumps/apache_dump", @apache_zone);
