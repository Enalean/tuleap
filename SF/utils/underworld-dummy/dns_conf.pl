#!/usr/bin/perl
#
# $Id$
#
use DBI;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions

# LJ
$dns_master_file = "/usr/local/domain/data/primary/codex.zone";

&db_connect;

#LJ @dns_zone = open_array_file("dns.zone");
@dns_zone = open_array_file($dns_master_file);

#
# Update the Serial Number
#

#LJ instead of hardcoding the line number where the serial number is
#LJ let's look for the keyword Serial in the comment field
#LJ $date_line = $dns_zone[1];

my $i=0;
foreach (@dns_zone) {
   last if /Serial/;
   $i++;
}
$date_line = $dns_zone[$i];

#LJ The way the serial number was extracted was really
#LJ not robust enough.
#LJ $date_line =~ s/\t\t\t/\t/;
#LJ ($blah,$date_str,$comments) = split("	", $date_line);

($blah,$date_str,$comments) = ($date_line =~ /^(\s*)(\d+)(.*)/);
$date = $date_str;

$serial = substr($date, 8, 2);
$old_day = substr($date, 6, 2);

$serial++;

$now_string = strftime "%Y%m%d", localtime;
$new_day = substr($now_string, 6, 2);

if ($old_day != $new_day) { $serial = "01"; }

$new_serial = $now_string.$serial;

#LJ $dns_zone[1] = "		$blah	$new_serial	$comments";
$dns_zone[$i] = "$blah"."$new_serial"."$comments"."\n";

write_array_file($dns_master_file, @dns_zone);

#
# grab Table information
#
my $query = "SELECT http_domain,unix_group_name,group_name,unix_box FROM groups WHERE http_domain LIKE '%.%' AND status = 'A'";
my $c = $dbh->prepare($query);
$c->execute();

while(my ($http_domain,$unix_group_name,$group_name,$unix_box) = $c->fetchrow()) {

	($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$unix_box.$sys_default_domain");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);

# LJ In the Source Forge version each project had its own IN A record
# and MX record so that sending mail to john.doe@myproject.codex.xerox.com
# was actually valid. On CodeX we do not want that. Just Web and CVS IP address

# LJ	push @dns_zone, sprintf("%-24s%-16s",$unix_group_name,"IN\tA\t" . "$ip\n");
# LJ	push @dns_zone, sprintf("%-24s%-28s","", "IN\tMX\t" . "mail1.codex.xerox.com.\n");
	push @dns_zone, sprintf("%-24s%-16s",$unix_group_name,"IN\tCNAME\t" . "$sys_fullname."."\n");
	push @dns_zone, sprintf("%-24s%-30s","cvs.".$unix_group_name,"IN\tCNAME\t" . "cvs1.$sys_default_domain."."\n\n");
}

# Retrieve the dummy's home directory
($name,$passwd,$uid,$gid,$quota,$comment,$gcos,$dir,$shell,$expire) = getpwnam("dummy");

write_array_file("$dir/dumps/dns_dump", @dns_zone);
