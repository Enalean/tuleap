# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0001.sql 399 2002-11-15 08:35:35Z sbouhet $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Add theme and font size for the user preferences.
#
# References:
# Task #2025
#
# Dependencies:
# None
#
# 
alter table user ADD fontsize INT UNSIGNED NOT NULL DEFAULT 0;
alter table user ADD theme varchar(50);
