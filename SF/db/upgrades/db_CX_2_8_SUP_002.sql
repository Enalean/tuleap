# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
# Database upgrade script 
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Add entries in permissions_values table, corresponding to 'News' item.
# Default permission is 'read for registered users'
#
#
# References:
# no
#
# Dependencies:
# no
#
# 
# SQL script comes next...
#

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) values ("NEWS_READ",100,0);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) values ("NEWS_READ",1,0);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) values ("NEWS_READ",2,1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) values ("NEWS_READ",3,0);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) values ("NEWS_READ",4,0);
