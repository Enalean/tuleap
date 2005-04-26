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
# update artifact_field table for multi_assigned_to fields and set default value = 100 (none) where
# the default value was still on '' (as Task template before db upgrade db0020.sql)
# 
# update 
#
# References:
# bug #2520
#
# Dependencies:
# none
#
#

## Change the default value of the multi_assigned_to field of Trackers from '' to 100 (None)
UPDATE artifact_field SET default_value = '100' WHERE field_name = 'multi_assigned_to' AND default_value = '';