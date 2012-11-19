#!/usr/bin/perl -U
#
# archive-deleted-items source_path destination_path
#

use warnings;
use strict;
use File::Copy;

my $destination_path = '';
my $source_path      = '';

# Treat arguments
if ($#ARGV ne 1) {
    die("Usage: $0 source_path destination_path\n");
}

if ($ARGV[0] =~ /^(.*)(\/(.*))$/) {
    $source_path = $ARGV[0];
} else {
    die("Invalid source path\n");
}

if ($ARGV[1] =~ /^(.*)(\/(.*))$/) {
    $destination_path = $ARGV[1];
} else {
    die("Invalid destination path\n");
}

if (!copy($source_path, $destination_path)) {
    die("FAILURE: cannot copy file ($!)\n");
}

print "OK\n";