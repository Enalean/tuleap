#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
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
#    This Perl include file mimics some of the functions in www/include/exit.php
#    to allow Perl scripts to handle exit errors and messages


sub exit_not_logged_in {

  my $redirect = "http://codex.xerox.com/account/login.php?return_to=".urlencode("http://".$ENV{'HTTP_HOST'}.$ENV{'REQUEST_URI'});
  print "Content-type: text/html\n"; 
  print "Location: $redirect\n";
  print "\n";

}

1;
