# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0026.sql 1308 2005-01-11 15:00:34Z guerin $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
#   Add a rank field in frs_package table in order give the possibility to
#   order packages.
# 
# SQL script comes next...
#
ALTER TABLE frs_package ADD rank INT( 11 ) DEFAULT '0' NOT NULL ;


