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
#    This Perl include file mimics some of the fucntion in www/include/user.php
#    to allow Perl scripts to handle exit errors and messages


use vars qw ( %G_SESSION %G_USER %cookies);

sub user_isloggedin {
  return defined($G_USER{'user_id'});
}

1;
