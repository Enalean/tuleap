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

while(my ($http_domain,$unix_group_name,$group_name,$unix_box) = $c->fetchrow()) {

	($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$unix_box.codex.xerox.com");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);

# LJ Custom log used to be on a per project basis but is now
# in one single file
#    "  CustomLog /home/groups/$unix_group_name/log/combined_log combined\n",
	push @apache_zone,
	( "<VirtualHost $ip>\n",
	  "  ServerName $unix_group_name.codex.xerox.com\n",
	  "  DocumentRoot /home/groups/$unix_group_name/htdocs/\n",
	  "  CustomLog logs/vhosts-access_log combined\n",
	  "  ScriptAlias /cgi-bin/ /home/groups/$unix_group_name/cgi-bin/\n",
	  "</VirtualHost>\n\n");

}

write_array_file("/home/dummy/dumps/apache_dump", @apache_zone);
