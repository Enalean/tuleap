#!/usr/bin/perl
#
# SourceForge: Breaking Down the Barriers to Open Source Development
# Copyright 1999-2000 (c) The SourceForge Crew
# http:#sourceforge.net
#
# $Id$

use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

#one hour ago for projects
$then=(time()-3600);
$rel = $dbh->prepare("DELETE FROM groups WHERE status='I' and register_time < '$then'");
$rel->execute();

#one week ago for users
$then=(time()-604800);
$rel = $dbh->prepare("DELETE FROM user WHERE status='P' and add_date < '$then'");
$rel->execute();

#one week ago for sessions
$then=(time()-604800);
$rel = $dbh->prepare("DELETE FROM session WHERE time < '$then'");
$rel->execute();
