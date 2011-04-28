#!/usr/bin/perl

use strict;
use warnings;

my $service = '/sbin/service';

my $useHttp     = 1;
my $useMailman  = 0;
my $useCVS      = 0;
my $useMysql    = 1;
my $useOpenfire = 0;

#
# Check environment
# 
my @currentUser = getpwnam(getlogin());
my $uid = $currentUser[2];
if ($uid != 0) {
    print "Must be run as root\n";
    usage();
}
if ($#ARGV != 0) {
    usage();
}

#
# Check argument
#
if ($ARGV[0] eq 'start') {
    start();
}
elsif ($ARGV[0] eq 'stop') {
    stop();
}
elsif ($ARGV[0] eq 'unfreeze') {
    start();
}
elsif ($ARGV[0] eq 'freeze') {
    stop();
}
else {
    usage();
}

# End of script

###############################################################################

# Functions

sub usage {
    print <<EOF;
codendiDaemon.pl (start|stop|freeze|unfreeze)
EOF
    exit 1;
}

sub stop {
    if ($useHttp) {
	system("$service httpd stop");
    }
    if ($useMailman) {
	system("$service mailman stop");
    }
    if ($useCVS) {
	system("$service xinetd stop");
    }
    if ($useMysql) {
	system("$service mysqld stop");
    }
}

sub start {
    if ($useMysql) {
	system("$service mysqld start");
    }
    if ($useCVS) {
	system("$service xinetd start");
    }
    if ($useMailman) {
	system("$service mailman start");
    }
    if ($useHttp) {
	system("$service httpd start");
    }
}
