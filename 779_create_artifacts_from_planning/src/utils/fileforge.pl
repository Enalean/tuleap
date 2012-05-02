#!/usr/bin/perl -UT

#
# fileforge filename-1.0.gz unix_group_name/p1245_r765/filename-1.0.gz_1299584197
#

# Set default path (required by taint mode)
$ENV{'PATH'} = '/usr/bin:/bin';

use warnings;
use strict;
use File::Copy;

my $localinc = $ENV{'CODENDI_LOCAL_INC'} || "/etc/codendi/conf/local.inc"; # Local Include file for database username and password
my %conf = load_local_config($localinc);

my $src_dir = $conf{'ftp_incoming_dir'}   || '/var/lib/codendi/ftp/incoming/';
my $dst_dir = $conf{'ftp_frs_dir_prefix'} || '/var/lib/codendi/ftp/codendi/';
my $file    = '';
my $group   = '';
my $dst_file = '';

# Ensure there is a trailing slash
if ($src_dir !~ '/\/$/') {
    $src_dir = "$src_dir/";
}
if ($dst_dir !~ '/\/$/') {
    $dst_dir = "$dst_dir/";
}


# Treat arguments
if ($#ARGV ne 1) {
    die("Usage: $0 file group\n");
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

my $src_file = $src_dir.$file;

$dst_dir = $dst_dir.$group;
if (! -d $dst_dir) {
    if (!mkdir($dst_dir, 0775)) {
        die("FAILURE: destination directory could not be created ($!)\n");
    }
}

# add 'group' read and remove 'other' perms
chmod(0640, $src_file);


$dst_file  = $dst_dir.$dst_file;
# print "Rename $src_file $dst_file\n";

if (!rename($src_file, $dst_file)) {
    die("FAILURE: cannot move file ($!)\n");
}

print "OK\n";


###############################################################################
# Sub routines
###############################################################################


sub load_local_config {
        my $filename = shift(@_);
        my ($foo, $bar);
        my %conf;

        # open up database include file and get the database variables
        open(FILE, $filename) || die "Can't open $filename: $!\n";
        while (<FILE>) {
            chomp;
            # Remove comments
            next if ( /^\s*\/\// );
            # Remove empty lines
            next if ( /^\s*$/ );
            # Remove php headers & footers
            next if ( /^\s*<\?php.*/);
            next if ( /^\s*\?>.*/);

            # Remove trailing comment if any
            s/;\s*\/\/.*//;
            ($foo, $bar) = split(/\s*=\s*/);
            if ($foo) {
                # Remove leading $
                $foo =~ s/^\$//;
                # Remove trailing ;
                $bar =~ s/\s*;\s*//;
                # Remove surronding quotes if any
                $bar =~ s/^"(.*)"$/$1/;

                $conf{$foo} = $bar
            };
        }
        close(FILE);

        return %conf;
}
