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
#   Add a rank field in doc_data table and a group_rank field in the 
#   doc_groups table in order give the possibility to order documents
#   and document groups.
#
# NOTE: this script is named db0024.sql on support branch!!
#
# SQL script comes next...
#
ALTER TABLE `doc_data` ADD `rank` INT(11) DEFAULT '0' NOT NULL ;
ALTER TABLE `doc_groups` ADD `group_rank` INT(11) DEFAULT '0' NOT NULL AFTER `groupname` ;
