# MySQL setup for CodeX
#
# This SQL script is to be run after the SourceForge.sql
# script. It puts inplace all the specific columns and
# tables for CodeX
#
# $Id$


#
# Table structure for table 'group_cvs_full_history'
#

CREATE TABLE group_cvs_full_history (
  group_id int(11) DEFAULT '0' NOT NULL,
  user_id int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  cvs_commits int(11) DEFAULT '0' NOT NULL,
  cvs_adds int(11) DEFAULT '0' NOT NULL,
  cvs_checkouts int(11) DEFAULT '0' NOT NULL,
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (day)
);

#
# The documents are now stored in longtext instead
# of (too short) text column (< 64 KBytes)
#

ALTER TABLE doc_data MODIFY data LONGTEXT NOT NULL;

#
# Add fields in the groups table for related IPs and
# Patents as well as required software.
#

ALTER TABLE groups ADD required_software TEXT AFTER register_purpose;
ALTER TABLE groups ADD patents_ips TEXT AFTER required_software;
ALTER TABLE groups ADD other_comments TEXT AFTER patents_ips;

#
# Add a field in the user table to store whether the
# user wants a permanent login or not. Default value is
# 0 (no sticky login)
#

ALTER TABLE user ADD sticky_login INTEGER DEFAULT '0' NOT NULL AFTER mail_va;

#
# Add a field in the user table to store the 2 MS-Windows
# encrypted password (the first one is the Win2K password
# and the second is the Windows NT compatible password - 
# separated by a ':'
#

ALTER TABLE user ADD windows_pw VARCHAR(80) DEFAULT '' NOT NULL;

#
# Add a field in the survey table to determine whether a survey
# can be taken by anonymous users (default is NO)
#

ALTER TABLE surveys ADD is_anonymous INTEGER DEFAULT '0' NOT NULL AFTER is_active;

#
# Add fields for customizable bug,support,patch form preamble
#

ALTER TABLE groups ADD bug_preamble TEXT NOT NULL;
ALTER TABLE groups ADD support_preamble TEXT NOT NULL;
ALTER TABLE groups ADD patch_preamble TEXT NOT NULL;
ALTER TABLE groups ADD pm_preamble TEXT NOT NULL;

#
# Modify patch table to hold uploaded file information
#

ALTER TABLE patch CHANGE code code LONGBLOB;
ALTER TABLE patch ADD filename VARCHAR(255) NOT NULL;
ALTER TABLE patch ADD filesize VARCHAR(50) NOT NULL;
ALTER TABLE patch ADD filetype VARCHAR(50) NOT NULL;

#
# Modify snippet_version table to hold uploaded file information
#

ALTER TABLE snippet_version CHANGE code code LONGBLOB;
ALTER TABLE snippet_version ADD filename VARCHAR(255) NOT NULL;
ALTER TABLE snippet_version ADD filesize VARCHAR(50) NOT NULL;
ALTER TABLE snippet_version ADD filetype VARCHAR(50) NOT NULL;

#
# Modify user_preferences table to hold longer pieces of information
# and  user_id+preference_name index must be unique
#

ALTER TABLE user_preferences CHANGE preference_name preference_name VARCHAR(255) NOT NULL;
ALTER TABLE user_preferences DROP INDEX `idx_user_pref_user_id`, ADD PRIMARY KEY (`user_id`,`preference_name`);
