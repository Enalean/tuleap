#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose: This Python file mimics some of the functions in www/include/exit.php
#    to allow Python scripts to handle exit errors and messages

import os
import urllib

def exit_not_logged_in():
    arg = {}
    redirect = "/account/login.php"
    if os.environ.has_key('REQUEST_URI'):
        arg['return_to'] = os.environ['REQUEST_URI']
        redirect += "?"+urllib.urlencode(arg)

    print "Content-type: text/html"
    print "Location: ",redirect
    print ""
