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
# Add new columns artifacts_opened and artifacts_closed to table stats_project
#
#
# References:
# See task #211
#
# Dependencies:
# none
#
#

ALTER TABLE stats_project ADD artifacts_opened smallint(6) NOT NULL default '0';
ALTER TABLE stats_project ADD artifacts_closed smallint(6) NOT NULL default '0';

ALTER TABLE stats_project_tmp ADD artifacts_opened smallint(6) NOT NULL default '0';
ALTER TABLE stats_project_tmp ADD artifacts_closed smallint(6) NOT NULL default '0';
