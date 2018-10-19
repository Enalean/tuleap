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

# Pending user accounts
# default (see local.inc) is 60 days
if ($sys_pending_account_lifetime != 0) {
  $then=(time()-3600*24*$sys_pending_account_lifetime);
  $rel = $dbh->prepare("DELETE FROM user WHERE status='P' and add_date < '$then'");
  $rel->execute();
}

# Default: 6 months ago for sessions (this is for permanent login)
# Can be modified in local.inc
if ($sys_session_lifetime != 0) {
  $then=(time()-$sys_session_lifetime);
  $rel = $dbh->prepare("DELETE FROM session WHERE time < '$then'");
  $rel->execute();
}
