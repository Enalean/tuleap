#!/usr/bin/perl
#
# $Id$
#
# sort.pl - Quick Perl script to sort /etc/group or /etc/passwd depending
#           on command line options

my ($filename,$junk,$x,$id,$userlist,$tmp_array,$file_array,$tmp_array);

$filename = shift;
$tmp_array = ();

if (!$filename) {
	print("Usage:\n");
	print("	sort.pl <filename>\n");
	exit 0;
}

open (FD, $filename) || die "Can't open $filename: $!\n";
@file_array = <FD>;
close(FD);

foreach (@file_array) {
	($junk,$junk,$id,$junk) = split(":", $_);

	$tmp_array[$id] = $_;
}

foreach (@tmp_array) {
	if ($_ ne '') {
		print $_;
	}
}
