#!/usr/bin/perl
#
# SourceForge: Breaking Down the Barriers to Open Source Development
# Copyright 1999-2000 (c) The SourceForge Crew
# http:#sourceforge.net
#
# 

use DBI;

require("../include.pl");  # Include all the predefined functions

&db_connect;

#one hour ago for invalid projects
$then=(time()-3600);
$rel = $dbh->prepare("DELETE FROM groups WHERE status='I' and register_time < '$then'");
$rel->execute();

# two weeks ago for pending user accounts
$then=(time()-3600*24*14);
$rel = $dbh->prepare("DELETE FROM user WHERE status='P' and add_date < '$then'");
$rel->execute();

# Default: 6 months ago for sessions (this is for permanent login)
# Can be modified in local.inc
$then=(time()-$sys_session_lifetime);
$rel = $dbh->prepare("DELETE FROM session WHERE time < '$then'");
$rel->execute();
