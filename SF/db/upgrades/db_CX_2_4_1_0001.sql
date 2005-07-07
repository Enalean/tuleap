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
# ...what is the modification about. Explain with full details.
#
#
# References:
# ... list bug, tasks... that relates to this script. If this task/bug 
# does not contain the list of source code file impacted by this change
# then list them here
#
# Dependencies:
# ... if other dbXXXX.sql files must be applied before that one list them
# here
#
# 
# SQL script comes next...
#
UPDATE `ugroup` SET `description` = 'ugroup_anonymous_users_desc_key' WHERE `group_id` = '100' AND `name` = 'anonymous_users';
UPDATE `ugroup` SET `description` = 'ugroup_registered_users_desc_key' WHERE `group_id` = '100' AND `name` = 'registered_users';
UPDATE `ugroup` SET `description` = 'ugroup_project_members_desc_key' WHERE `group_id` = '100' AND `name` = 'project_members';
UPDATE `ugroup` SET `description` = 'ugroup_project_admins_desc_key' WHERE `group_id` = '100' AND `name` = 'project_admins';
UPDATE `ugroup` SET `description` = 'ugroup_file_manager_admin_desc_key' WHERE `group_id` = '100' AND `name` = 'file_manager_admin';
UPDATE `ugroup` SET `description` = 'ugroup_document_tech_desc_key' WHERE `group_id` = '100' AND `name` = 'document_tech';
UPDATE `ugroup` SET `description` = 'ugroup_document_admin_desc_key' WHERE `group_id` = '100' AND `name` = 'document_admin';
UPDATE `ugroup` SET `description` = 'ugroup_wiki_admin_desc_key' WHERE `group_id` = '100' AND `name` = 'wiki_admin';
UPDATE `ugroup` SET `description` = 'ugroup_tracker_admins_desc_key' WHERE `group_id` = '100' AND `name` = 'tracker_admins';
UPDATE `ugroup` SET `description` = 'ugroup_tracker_techs_desc_key' WHERE `group_id` = '100' AND `name` = 'tracker_techs';
UPDATE `ugroup` SET `description` = 'ugroup_nobody_desc_key' WHERE `group_id` = '100' AND `name` = 'nobody';

UPDATE `ugroup` SET `name` = 'ugroup_anonymous_users_name_key' WHERE `group_id` = '100' AND `name` = 'anonymous_users';
UPDATE `ugroup` SET `name` = 'ugroup_registered_users_name_key' WHERE `group_id` = '100' AND `name` = 'registered_users';
UPDATE `ugroup` SET `name` = 'ugroup_project_members_name_key' WHERE `group_id` = '100' AND `name` = 'project_members';
UPDATE `ugroup` SET `name` = 'ugroup_project_admins_name_key' WHERE `group_id` = '100' AND `name` = 'project_admins';
UPDATE `ugroup` SET `name` = 'ugroup_file_manager_admin_name_key' WHERE `group_id` = '100' AND `name` = 'file_manager_admin';
UPDATE `ugroup` SET `name` = 'ugroup_document_tech_name_key' WHERE `group_id` = '100' AND `name` = 'document_tech';
UPDATE `ugroup` SET `name` = 'ugroup_document_admin_name_key' WHERE `group_id` = '100' AND `name` = 'document_admin';
UPDATE `ugroup` SET `name` = 'ugroup_wiki_admin_name_key' WHERE `group_id` = '100' AND `name` = 'wiki_admin';
UPDATE `ugroup` SET `name` = 'ugroup_tracker_admins_name_key' WHERE `group_id` = '100' AND `name` = 'tracker_admins';
UPDATE `ugroup` SET `name` = 'ugroup_tracker_techs_name_key' WHERE `group_id` = '100' AND `name` = 'tracker_techs';
UPDATE `ugroup` SET `name` = 'ugroup_nobody_name_key' WHERE `group_id` = '100' AND `name` = 'nobody';

