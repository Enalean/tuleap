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
# Allow anonymous access to wiki.
#
#
# References:
# SR #361
# 
# Dependencies:
# none
#
# 
# SQL script comes next...
#

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIATTACHMENT_READ',1);
