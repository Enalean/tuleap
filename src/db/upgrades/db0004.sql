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
# Add a field to indicate if a document is accessible to non register user
# Create a log table to log the access of all restricted document
#
# References:
# Task #2315
#
# Dependencies:
# None
#
# 
alter table doc_data ADD restricted_access INT UNSIGNED NOT NULL DEFAULT 0;

#
# Table structure for table 'doc_log'
#

CREATE TABLE doc_log (
  user_id int(11) NOT NULL default '0',
  docid int(11) NOT NULL default '0',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,docid),
  KEY time_idx (time),
  KEY docid_idx (docid)
) TYPE=MyISAM;
