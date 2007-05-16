#!/usr/bin/perl
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
# This file is licensed under the GPL License
# http://codex.xerox.com
#
# $Id$
#
use DBI;
use POSIX qw(strftime);

require("../include.pl");  # Include all the predefined functions

if ($sys_disable_subdomains) {
  exit(0);
}
&db_connect;

#
# Is current server master ?
#
my $server_is_master = is_current_server_master();

@dns_zone = open_array_file($dns_master_file);

#
# Update the Serial Number
#

my $i=0;
foreach (@dns_zone) {
   last if /Serial/;
   $i++;
}
$date_line = $dns_zone[$i];

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
my $query = "SELECT http_domain,unix_group_name,group_name,unix_box,location,server_id FROM groups g, service s WHERE g.http_domain LIKE '%.%' AND g.status = 'A' AND s.group_id = g.group_id AND s.short_name = 'svn'";
my $c = $dbh->prepare($query);
$c->execute();

while(my ($http_domain,$unix_group_name,$group_name,$unix_box, $location, $server_id) = $c->fetchrow()) {

	($name, $aliases, $addrtype, $length, @addrs) = gethostbyname("$unix_box.$sys_default_domain");
	@blah = unpack('C4', $addrs[0]);
	$ip = join(".", @blah);
	my $addedEntry = 0;

# CodeX - Uncomment the 2 lines below if you want mail  to john.doe@myproject.codex.xerox.com
# to be valid.

# CodeX	push @dns_zone, sprintf("%-24s%-16s",$unix_group_name,"IN\tA\t" . "$ip\n");
# CodeX	push @dns_zone, sprintf("%-24s%-28s","", "IN\tMX\t" . "mail1.codex.xerox.com.\n");
	if($server_is_master == 1
	   || ($location eq "satellite" &&  $server_id == $sys_server_id)) {
	    push @dns_zone, sprintf("%-24s%-16s",$unix_group_name," IN\tCNAME\t" . "$sys_fullname."."\n");
	    $addedEntry = 1;
	}
	if($server_is_master == 1) {
	    push @dns_zone, sprintf("%-24s%-30s","cvs.".$unix_group_name," IN\tCNAME\t" . "cvs1.$sys_default_domain."."\n");
	    $addedEntry = 1;
	}
	if(($location eq "master" && $server_is_master == 1) 
	   || ($location eq "satellite" &&  $server_id == $sys_server_id)) {
	    push @dns_zone, sprintf("%-24s%-30s","svn.".$unix_group_name," IN\tCNAME\t" . "svn1.$sys_default_domain."."\n");
	    $addedEntry = 1;
	}
	if($addedEntry) {
	    push @dns_zone, "\n";
	}
    }


write_array_file("$dump_dir/dns_dump", @dns_zone);
