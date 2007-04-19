# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0017.sql 880 2004-03-01 16:08:30Z guerin $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Add new field cvs_preamble in table groups
#
#
# References:
# See task #3139
#
# Dependencies:
# none
#
#

ALTER TABLE groups ADD cvs_preamble text NOT NULL;

