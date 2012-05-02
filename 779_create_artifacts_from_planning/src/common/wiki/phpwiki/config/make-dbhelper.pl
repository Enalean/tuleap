#!/usr/bin/perl -sn
# makefile helper to extract various settings from config/config.ini
# $Id: make-dbhelper.pl,v 1.2 2004/07/01 08:15:10 rurban Exp $

#if ($v eq 'DATABASE_TYPE' and /^\s*DATABASE_TYPE\s*=\s*(\w+)/) {
#    print "$1\n";
#    exit;
#}

# word split
if ($v eq 'DATABASE_DSN' and /^\s*DATABASE_DSN\s*=\s*"?([\w:\/@]+)/) {
    my $result = '';
    my $dsn = $1;
    $dsn =~ /^(.+?):\/\// 	and $result .= "$1 "; # backend
    $dsn =~ /.+\/(.+?)$/ 	and $result .= "$1 "; # database: everything after the last slash
    $dsn =~ /:\/\/(\w+):/ 	and $result .= "$1 "; # username (optional)
    $dsn =~ /:\/\/\w+:(\w+)@/ 	and $result .= "$1 "; # password (optional)
    print "$result\n";
    exit;
}

if ($v ne 'DATABASE_DSN') {
  if (/^\s*$v\s*=\s*"?([^;]+)$/) {
    print "$1\n";
    exit;
  }
}
