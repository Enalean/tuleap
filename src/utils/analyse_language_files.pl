#!/usr/bin/perl
#
# Tuleap
# Copyright (c) Enalean, 2018. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
#
#  License:
#      This file is licensed under the GNU General Public License
#
# Purpose:
#  Simple utility to analyse Language data.
#
# Usage:
#  Edit the directories below, and run the script. 
#  Use '-s' for silent mode (summary only)
#  Use '-v' for verbose mode (print also unused keys)
#
# Originaly written by Nicolas Guerin - 2005, Codendi Team, Xerox
#

use strict;

# Location of sources to analyse
my $base_dir=$ARGV[0];
my $source_dir="$base_dir/src";
my $plugin_src_dir="$base_dir/plugins";
# Language specific dir
my $tab_dir="$base_dir/site-content/";
my $plugin_tab_dir="$plugin_src_dir/*/site-content/";

# end of configuration

my %firstkey;
my %keys;

my %missing_keys_array;
my %special_keys_array;
my %tab_lines_array;
my %duplicate_keys_array;
my %unused_keys_array;
my %incorrect_keys_array;
my $used_lines=0;
my $total_keys=0;
my $special_keys=0;
my $silent_mode=0;
my $verbose_mode=0;

my $usage="usage: analyse_language_file.pl [-s] \
    -s: silent mode \
    -v: verbose mode, listing unused keys \
";


if ($ARGV[0]) {
  if ($ARGV[0] eq '-s') {
    $silent_mode=1;
  } elsif ($ARGV[0] eq '-v') {
    $verbose_mode=1;
  } else { print $usage; }
}

my @files=`find $source_dir $plugin_src_dir -name "*.class"`;
push @files,`find $source_dir $plugin_src_dir -name "*.php"`;
push @files,"$source_dir/www/projects";
push @files,"$source_dir/www/users";


#  remove the check on file common/error/Error_PermissionDenied.class.php
# becaus eit uses a variable as base
my $position = 0;
chomp $position;
foreach my $case (@files) {
    if ($case =~/\/common\/Error\/Error_PermissionDenied.class.php/ ) {
        last;
    }
    $position++;
}
@files = @files[0..($position-1),($position+1)..$#files];


print "***\n" if ($verbose_mode);
print "*** Reading Source Code\n" unless ($silent_mode);
print "***\n" if ($verbose_mode);

# Read all messages in the source code.
foreach my $filename (@files) {
  chomp $filename;
  open FILE,"$filename";
  #print "File: $filename\n";
  while(<FILE>) {
    while (/->getText\s*\(.([^,\'\"\s]+).[\s]*\,[\s]*(.)([^,\'\"\s]+)[^\)][\)\,]/) {
      #print "\t$1\t$2\t$3\n";}
      if (($2 ne "\'")&&($2 ne "\"")) { # not a regular 'string'
        $special_keys_array{"$1"}{"$3"}.="$filename ";
        $special_keys++;
      } else {
        $firstkey{"$1"}=1;
        if (!$keys{"$1"}{"$3"}) { $total_keys++;}
        $keys{"$1"}{"$3"}.="$filename ";
      }
      $used_lines++;
      # for multiple occurences on same line
      $_ =~ s/\->getText\s*\(.([^,\'\"\s]+).[\s]*\,[\s]*(.)([^,\'\"\s]+)[^\)][\)\,]//;
    }
  }
  close FILE;
}

if (!$silent_mode) {
  print "***\n" if ($verbose_mode);
  print "*** Reading Tab files\n";
  print "***\n" if ($verbose_mode);
}

my @lang_tab_dir=`(cd $tab_dir && /bin/ls -d */)`;

foreach my $my_tab_dir (@lang_tab_dir) {
  chomp $my_tab_dir;
  print "\n*** $my_tab_dir\n" unless ($silent_mode);
  my $missing_keys=0;
  my $tab_lines=0;
  my $duplicate_keys=0;
  my $unused_keys=0;
  my $incorrect_keys=0;
  my %tab_keys;

  # Read messages in tab files
  my @tab_files=`find $tab_dir/$my_tab_dir $plugin_tab_dir/$my_tab_dir -name "*.tab"`;
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
          print "$fileonly($linecount) - Duplicate keys:$key1 $key2 - $line" unless ($silent_mode);
          $duplicate_keys++;
        } else {
          $tab_keys{"$1"}{"$2"}.="$3";
          $tab_lines++;
        }
      } else {
        print "$fileonly($linecount) - Error: $line" unless ($silent_mode);
        $incorrect_keys++;
      }
    }
    close FILE;
  }


  if (!$silent_mode) {
    print "***\n" if ($verbose_mode);
    print "*** Checking missing keys\n";
    print "***\n" if ($verbose_mode);
  }
  foreach my $key1 (keys %keys) {
    foreach my $key2 (keys %{ $keys{"$key1"}}) {
      if (! $tab_keys{"$key1"}{"$key2"}) {
        print "Missing:  $key1\t$key2\t (".$keys{"$key1"}{"$key2"}.")\n" unless ($silent_mode);
        $missing_keys++;
      }
    }
  }
  $missing_keys_array{$my_tab_dir}=$missing_keys;


  if (!$silent_mode) {
    print "***\n" if ($verbose_mode);
    print "*** Checking unused keys\n";
    print "***\n" if ($verbose_mode);
  }
  foreach my $key1 (keys %tab_keys) {
    foreach my $key2 (keys %{ $tab_keys{"$key1"}}) {
      if (! $keys{"$key1"}{"$key2"}) {
        print "Unused:  $key1\t$key2\t (".$tab_keys{"$key1"}{"$key2"}.")\n" unless ($silent_mode || !$verbose_mode);
        $unused_keys++
      }
    }
  }


  $missing_keys_array{$my_tab_dir}=$missing_keys;
  $tab_lines_array{$my_tab_dir}=$tab_lines;
  $duplicate_keys_array{$my_tab_dir}=$duplicate_keys;
  $unused_keys_array{$my_tab_dir}=$unused_keys;
  $incorrect_keys_array{$my_tab_dir}=$incorrect_keys;

}

my $exit_code = 0;

print "\nReport:\n";
print "*******\n";
print "$used_lines \tgetText() calls\n";
print "$total_keys \tunique keys\n";
print "$special_keys \tspecial keys\n";
foreach my $lang (keys %missing_keys_array) {
  my $percent=100*(1 - ($total_keys-($tab_lines_array{$lang}-$duplicate_keys_array{$lang}-$unused_keys_array{$lang}-$incorrect_keys_array{$lang}))/$total_keys);
  $percent = sprintf('%.2f', $percent);
  print "Language: $lang:\n";
  print "  ".$tab_lines_array{$lang}." \tentries in .tab files\n";
  print "  ".$duplicate_keys_array{$lang}." \tduplicate keys\n";
  print "  ".$missing_keys_array{$lang}." \tmissing keys\n";
  print "  ".$unused_keys_array{$lang}." \tunused keys\n";
  print "  ".$incorrect_keys_array{$lang}." \tincorrect keys\n";
  print "  -> $percent% complete\n";
  if ($duplicate_keys_array{$lang} ne 0 || $missing_keys_array{$lang} ne 0 || $incorrect_keys_array{$lang} ne 0) {
    $exit_code = 1;
  }
}
exit $exit_code;

foreach my $key1 (keys %keys) {
  foreach my $key2 (keys %{ $keys{"$key1"}}) {
    print "* $key1 * $key2 *".$keys{"$key1"}{"$key2"}."\n";
  }
}


