#!/usr/bin/perl
#
# $Id$
#
use DBI;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions

&db_connect;

@dns_zone = open_array_file("dns.zone");

#
# Update the Serial Number
#
$date_line = $dns_zone[1];

$date_line =~ s/\t\t\t/\t/;
        
($blah,$date_str,$comments) = split("	", $date_line);

$date = $date_str;

$serial = substr($date, 8, 2);
$old_day = substr($date, 6, 2);

$serial++;

$now_string = strftime "%Y%m%d", localtime;
$new_day = substr($now_string, 6, 2);

if ($old_day != $new_day) { $serial = "01"; }

$new_serial = $now_string.$serial;

$dns_zone[1] = "		$blah	$new_serial	$comments";

write_array_file("dns.zone", @dns_zone);

#
# grab Table information
#
my $query = "SELECT http_domain,unix_group_name,group_name,unix_box FROM groups WHERE http_domain LIKE '%.%' AND status = 'A'";
my $c = $dbh->prepare($query);
$c->execute();

while(my ($http_domain,$unix_group_name,$group_name,$unix_box) = $c->fetchrow()) {

	($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$unix_box.sourceforge.net");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);

	push @dns_zone, sprintf("%-24s%-16s",$unix_group_name,"IN\tA\t" . "$ip\n");
	push @dns_zone, sprintf("%-24s%-28s","", "IN\tMX\t" . "mail1.sourceforge.net.\n");
	push @dns_zone, sprintf("%-24s%-30s","cvs.".$unix_group_name,"IN\tCNAME\t" . "cvs1.sourceforge.net."."\n\n");
}

write_array_file("/home/dummy/dumps/dns.sourceforge.net", @dns_zone);
