# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0024.sql 1300 2005-01-06 15:01:25Z schneide $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the
# CodeX source code. All references are included
# below.
#
# Description
# track cvs and svn accesses through viewcvs browsing
#
#
# References:
# Task #986
#
# Dependencies:
# none
#

ALTER TABLE group_svn_full_history ADD COLUMN svn_browse int(11) NOT NULL default '0' AFTER svn_access_count;
ALTER TABLE group_cvs_full_history ADD COLUMN cvs_browse int(11) NOT NULL default '0' AFTER cvs_checkouts;

