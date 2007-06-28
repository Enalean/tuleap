# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# 
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Insert a new file type in the frs_filetype table (binary installer)
#
#
# References:
# See SR #102165
#
# Dependencies:
# None
#
# 
# SQL script comes next...
#

INSERT INTO frs_filetype VALUES ('3150','Binary installer');
