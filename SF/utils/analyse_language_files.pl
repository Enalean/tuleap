#!/usr/bin/perl
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2005. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#  Simple utility to analyse Language data.
#  Currently limited to one language.
#
# Usage:
#  Edit the directories below, and run the script without argument!
#
# Originaly written by Nicolas Guerin - 2005, CodeX Team, Xerox
#

use strict;

# No command line argument yet...

# Location of CodeX sources to analyse
my $source_dir="../../SF";
# Language specific dir
my $tab_dir="../../site-content/en_US";

# end of configuration

my %firstkey;
my %keys;
my %tab_keys;

my $tab_lines=0;
my $used_lines=0;
my $duplicate_keys=0;
my $missing_keys=0;
my $unused_keys=0;
my $incorrect_keys=0;


my @files=`find $source_dir -name "*.class"`;
push @files,`find $source_dir -name "*.php"`;
push @files,"$source_dir/www/foundry";
push @files,"$source_dir/www/projects";
push @files,"$source_dir/www/users";


print "***\n";
print "*** Reading Source Code\n";
print "***\n";

# Read all messages in the source code.
foreach my $filename (@files) {
  chomp $filename;
  open FILE,"$filename";
  #print "File: $filename\n";
  while(<FILE>) {
    while (/\$Language->getText\(.([^,\'\"\s]+).[\s]*\,[\s]*.([^,\'\"\s]+)[^\)][\)\,]/) {
      #print "\t$1\t$2\n";
      $firstkey{"$1"}=1;
      $keys{"$1"}{"$2"}.="$filename ";
      $used_lines++;
      # for multiple occurences on same line
      $_ =~ s/\$Language->getText\(.([^,\'\"\s]+).[\s]*\,[\s]*.([^,\'\"\s]+)[^\)][\)\,]//;
    }
  }
  close FILE;
}


print "***\n";
print "*** Reading Tab files\n";
print "***\n";

# Read messages in tab files
my @tab_files=`find $tab_dir -name "*.tab"`;
foreach my $filename (@tab_files) {
  next if ($filename =~ /\/Base\.tab$/); # GForge file
  chomp $filename;

  # For readability
  my $fileonly;
  if ($filename=~m|.*/([^/]+)$|) {
    $fileonly=$1;
  } else {
    $fileonly=$filename;
  }

  open FILE,"$filename";
  #print "File: $filename\n";
  my $linecount=0;
  while(<FILE>) {
    my $line=$_;
    $linecount++;
    next if (/^\#/); # comment
    next if (/^$/); # empty
    if (/^([^\t]*)\t([^\t]*)\t(.*)$/) {
      my $key1=$1;
      my $key2=$2;
      my $val=$3;
      if ($tab_keys{"$key1"}{"$key2"}) {
        print "$fileonly($linecount) - Duplicate keys:$key1 $key2 - $line";
        $duplicate_keys++;
      } else {
        $tab_keys{"$1"}{"$2"}.="$3";
        $tab_lines++;
      }
    } else {
      print "$fileonly($linecount) - Error: $line";
      $incorrect_keys++;
    }
  }
  close FILE;
}


print "***\n";
print "*** Checking missing keys\n";
print "***\n";
foreach my $key1 (keys %keys) {
  foreach my $key2 (keys %{ $keys{"$key1"}}) {
      if (! $tab_keys{"$key1"}{"$key2"}) {
        print "Missing:  $key1\t$key2\t (".$keys{"$key1"}{"$key2"}.")\n";
        $missing_keys++;
      }
  }
}

print "***\n";
print "*** Checking unused keys\n";
print "***\n";
foreach my $key1 (keys %tab_keys) {
  foreach my $key2 (keys %{ $tab_keys{"$key1"}}) {
      if (! $keys{"$key1"}{"$key2"}) {
        print "Unused:  $key1\t$key2\t (".$tab_keys{"$key1"}{"$key2"}.")\n";
        $unused_keys++
      }
  }
}


print "\nReport:\n";
print "*******\n";
print "$tab_lines \tentries in .tab files\n";
print "$used_lines \tgetText() calls\n";
print "$duplicate_keys \tduplicate keys\n";
print "$missing_keys \tmissing keys\n";
print "$unused_keys \tunused keys\n";
print "$incorrect_keys \tincorrect keys\n";

exit 0;

foreach my $key1 (keys %keys) {
  foreach my $key2 (keys %{ $keys{"$key1"}}) {
    print "* $key1 * $key2 *".$keys{"$key1"}{"$key2"}."\n";
  }
}


