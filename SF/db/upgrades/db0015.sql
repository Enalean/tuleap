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
# Add new field instantiate_for_new_projects in table artifact_group_list
#
#
# References:
# See task #3119
#
# Dependencies:
# none
#
#

ALTER TABLE artifact_group_list ADD instantiate_for_new_projects int(11) NOT NULL default '0';

UPDATE artifact_group_list SET  instantiate_for_new_projects=1 WHERE group_id=100 AND group_artifact_id IN (1,2,3);
