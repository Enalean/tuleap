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
