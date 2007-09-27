# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
#   Modify size of the 'shell' column in order to fit '/usr/local/bin/cvssh-restricted'
# 
# SQL script comes next...
#

# NOTE: change '/bin/bash' to '/sbin/nologin' if you do not offer shell access by default.
ALTER TABLE user MODIFY shell varchar(50) NOT NULL default '/bin/bash';


