# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0010.sql 506 2003-05-15 17:03:05Z ljulliar $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Change the default value of the status_id field to open (1)
# instead of None (100) which does not exist anyway
#
#
# References:
# Bug #
#
# Dependencies:
# ... if other dbXXXX.sql files must be applied before that one list them
# here
#
# 
# SQL script comes next...
#
ALTER TABLE bug CHANGE status_id status_id INT(11) DEFAULT '1' NOT NULL;