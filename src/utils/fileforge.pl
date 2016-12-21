#!/usr/bin/perl -UT

#
# fileforge filename-1.0.gz unix_group_name/p1245_r765/filename-1.0.gz_1299584197 /var/lib/tuleap/ftp/tuleap
#

# Set default path (required by taint mode)
$ENV{'PATH'} = '/usr/bin:/bin';

use warnings;
use strict;
use File::Copy;

my $dst_dir  = '';
my $file     = '';
my $group    = '';
my $dst_file = '';
my $src_dir  = '';

# Treat arguments
if ($#ARGV ne 3) {
    die("Usage: $0 file group src_dir dst_dir\n");
}
if ($ARGV[0] =~ /^(.*)$/) {
    $file = $1;
} else {
    die("First argument invalid\n");
}
if ($ARGV[1] =~ /^(.*)(\/(.*))$/) {
    $group = $1;
    $dst_file = $2;
} else {
    die("Second argument invalid\n");
}
if ($ARGV[2] =~ /^(.*)$/) {
    $src_dir = $1;
} else {
    die("Third argument invalid\n");
}
if ($ARGV[3] =~ /^(.*)$/) {
    $dst_dir = $1;
} else {
    die("Fourth argument invalid\n");
}

# Ensure there is a trailing slash
if ($src_dir !~ '/\/$/') {
    $src_dir = "$src_dir/";
}

if ($dst_dir !~ '/\/$/') {
    $dst_dir = "$dst_dir/";
}

my $src_file = $src_dir.$file;

$dst_dir = $dst_dir.$group;
if (! -d $dst_dir) {
    if (!mkdir($dst_dir, 0775)) {
        die("FAILURE: destination directory could not be created ($!)\n");
    }
}


$dst_file  = $dst_dir.$dst_file;
# print "Rename $src_file $dst_file\n";

if (!move($src_file, $dst_file)) {
    die("FAILURE: cannot move file ($!)\n");
}

# add 'group' read and remove 'other' perms
system("setfacl -b \"$dst_file\"");
chmod(0640, $dst_file);
system("chgrp ftpadmin \"$dst_file\"");

print "OK\n";
