# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0011.sql 542 2003-06-20 15:45:33Z ljulliar $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# ...what is the modification about. Explain with full details.
#
#
# References:
# ... list bug, tasks... that relates to this script. If this task/bug 
# does not contain the list of source code file impacted by this change
# then list them here
#
# Task #2477
#
# Dependencies:
# ... if other dbXXXX.sql files must be applied before that one list them
# here
#
# None
#
# 
# SQL script comes next...
#


# CREATE cvs support tables

CREATE TABLE cvs_checkins (
  type enum('Change','Add','Remove'),
  ci_when datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  whoid mediumint(9) DEFAULT '0' NOT NULL,
  repositoryid mediumint(9) DEFAULT '0' NOT NULL,
  dirid mediumint(9) DEFAULT '0' NOT NULL,
  fileid mediumint(9) DEFAULT '0' NOT NULL,
  revision varchar(32) binary ,
  stickytag varchar(255) binary DEFAULT '' NOT NULL,
  branchid mediumint(9) DEFAULT '0' NOT NULL,
  addedlines int(11) DEFAULT '999' NOT NULL,
  removedlines int(11) DEFAULT '999' NOT NULL,
  commitid int(11) DEFAULT '0' NOT NULL,
  descid int(11) DEFAULT '0' NOT NULL,
  UNIQUE repositoryid (repositoryid,dirid,fileid,revision),
  KEY ci_when (ci_when),
  KEY repositoryid_2 (repositoryid),
  KEY dirid (dirid),
  KEY fileid (fileid),
  KEY branchid (branchid)
) TYPE=MyISAM;

CREATE TABLE cvs_commits (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  comm_when timestamp,
  whoid mediumint(9) DEFAULT '0' NOT NULL,
  KEY whoid (whoid),
  PRIMARY KEY (id)
) TYPE=MyISAM;

CREATE TABLE cvs_descs (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  description text,
  hash bigint(20) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT (description), 
  KEY hash (hash)
) TYPE=MyISAM;

CREATE TABLE cvs_dirs (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  dir varchar(128) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE dir (dir)
);

CREATE TABLE cvs_files (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  file varchar(128) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE file (file)
);

CREATE TABLE cvs_repositories (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  repository varchar(64) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE repository (repository)
);

CREATE TABLE cvs_tags (
  repositoryid mediumint(9) DEFAULT '0' NOT NULL,
  branchid mediumint(9) DEFAULT '0' NOT NULL,
  dirid mediumint(9) DEFAULT '0' NOT NULL,
  fileid mediumint(9) DEFAULT '0' NOT NULL,
  revision varchar(32) binary DEFAULT '' NOT NULL,
  KEY repositoryid_2 (repositoryid),
  KEY dirid (dirid),
  KEY fileid (fileid),
  KEY branchid (branchid)
);

CREATE TABLE cvs_branches ( 
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  branch varchar(64) binary DEFAULT '' NOT NULL, 
  PRIMARY KEY (id), 
  UNIQUE branch (branch)  
); 

CREATE TABLE cvs_tracks ( 
  group_artifact_id int(11),
  tracker varchar(64) binary DEFAULT '' NOT NULL, 
  artifact_id int(11) NOT NULL, 
  commit_id int(11) NOT NULL	
); 


# create a boolean cvs_tracker field in group table (default true)
alter table groups ADD cvs_tracker int(11) NOT NULL default '1';
alter table groups ADD cvs_events_mailing_list varchar(64) binary DEFAULT NULL;
alter table groups ADD cvs_events_mailing_header varchar(64) binary DEFAULT NULL;

# update existing projects with cvs_tracker field to false
update groups set cvs_tracker='0';

