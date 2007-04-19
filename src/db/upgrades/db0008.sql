# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0008.sql 465 2003-02-26 15:45:36Z sbouhet $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Add project_type in groups
# Add also the project_type table
#
# References:
# Task #2559
#
# Dependencies:
# None
#
# 
alter table groups ADD project_type int(11) NOT NULL default '0';

#
# Table structure for table 'project_type' for project type
#

CREATE TABLE project_type (
  project_type_id int(11) NOT NULL,
  label varchar(30) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  PRIMARY KEY  (project_type_id),
  KEY project_label_idx (label)
) TYPE=MyISAM;

# None value for project_type
#
INSERT INTO project_type VALUES (100,'None','None');
