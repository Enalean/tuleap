#
# Copyright (c) Enalean 2014 - Present. All rights reserved
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#

#
# Purpose:
#    Insert default values in all the Tuleap tables.



#
# Default Data for 'user'
#
#
# Insert user 'none' with reserved user_id 100
# Also create the 'admin' user with default password 'siteadmin' and make
# it a member of the group_id 1 later on so that he as a super user status
# for the entire site

INSERT INTO user SET \
        user_id = 70, \
        user_name = 'forge__function', \
        email = 'noreply@_DOMAIN_NAME_', \
        realname = 'Tuleap Functions', \
        register_purpose = NULL, \
        status = 'S', \
        ldap_id = NULL, \
        add_date = 1704707115, \
        confirm_hash = NULL, \
        mail_siteupdates = 0, \
        mail_va = 0, \
        sticky_login = 0, \
        authorized_keys = NULL, \
        email_new = NULL, \
        timezone = 'UTC', \
        language_id = 'en_US', \
        last_pwd_update = '0';

INSERT INTO user_access SET \
        user_id = 70, \
        last_access_date = '0';

INSERT INTO user SET \
        user_id = 100, \
        user_name = 'None', \
        email = 'noreply@_DOMAIN_NAME_', \
        realname = '0', \
        register_purpose = NULL, \
        status = 'S', \
        ldap_id = NULL, \
        add_date = 940000000, \
        confirm_hash = NULL, \
        mail_siteupdates = 0, \
        mail_va = 0, \
        sticky_login = 0, \
        authorized_keys = NULL, \
        email_new = NULL, \
        timezone = 'UTC', \
        language_id = 'en_US', \
        last_pwd_update = '0';

INSERT INTO user_access SET \
        user_id = 100, \
        last_access_date = '0';

INSERT INTO user SET \
        user_id = 101, \
        user_name = 'admin', \
        email = 'codendi-admin@_DOMAIN_NAME_', \
        password = 'SITEADMIN_PASSWORD', \
        realname = 'Site Administrator', \
        register_purpose = NULL, \
        status = 'A', \
        ldap_id = NULL, \
        add_date = 940000000, \
        confirm_hash = NULL, \
        mail_siteupdates = 0, \
        mail_va = 0, \
        sticky_login = 0, \
        authorized_keys = NULL, \
        email_new = NULL, \
        timezone = 'UTC', \
        language_id = 'en_US', \
        last_pwd_update = '0';

INSERT INTO user_access SET \
        user_id = 101, \
        last_access_date = '0';

#
# Default Data for 'groups'
#
# Note: if you change the 'group' structure, do not forget to update the IM group (no 47) in the IM plugin.

INSERT INTO `groups` SET \
  group_id = '1', \
  group_name = 'Administration Project', \
  access = 'public', \
  status = 'A', \
  unix_group_name = 'admin', \
  http_domain = 'admin._DOMAIN_NAME_', \
  short_description = 'Administration Project', \
  svn_box = 'svn1', \
  register_time = 940000000, \
  rand_hash = '', \
  type = '1', \
  built_from_template = '100', \
  svn_tracker = '1', \
  svn_mandatory_ref = '0', \
  svn_events_mailing_header = '', \
  svn_preamble = '', \
  svn_commit_to_tag_denied ='0' ;

INSERT INTO `groups` SET \
  group_id = '46', \
  group_name = 'Site News', \
  access = 'private', \
  status = 'A', \
  unix_group_name = 'sitenews', \
  http_domain = 'sitenews._DOMAIN_NAME_', \
  short_description = 'Site News Private Project. All Site News should be posted from this project', \
  svn_box = 'svn1', \
  register_time = 940000000, \
  rand_hash = '', \
  type = '1', \
  built_from_template = '100', \
  svn_tracker = '0', \
  svn_mandatory_ref = '0', \
  svn_events_mailing_header = '', \
  svn_preamble = '', \
  svn_commit_to_tag_denied ='0' ;


INSERT INTO `groups` SET \
  group_id = '100', \
  group_name = 'Default Site Template', \
  access = 'private', \
  status = 's', \
  unix_group_name = 'none', \
  http_domain = '', \
  short_description = 'The default Tuleap template', \
  svn_box = '', \
  register_time = 940000000, \
  rand_hash = '', \
  type = '2', \
  built_from_template = '100', \
  svn_tracker = '1', \
  svn_mandatory_ref = '0', \
  svn_events_mailing_header = '', \
  svn_preamble = '', \
  svn_commit_to_tag_denied ='0' ;


INSERT INTO groups_notif_delegation_message SET \
  group_id = '100', \
  msg_to_requester = 'member_request_delegation_msg_to_requester';

#
# Default Data for 'user_group'
#
# Make the 'admin' user part of the default Admin Project so that he
# becomes a super user
# flags after 'A' are: bug,forum,project,patch,support,file,wiki,svn,news

INSERT INTO user_group VALUES (1, 101, 1, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2);

#
#  Default data for group_type
#
INSERT INTO group_type VALUES ('1','project');
INSERT INTO group_type VALUES ('2','template');
INSERT INTO group_type VALUES ('3','test_project');

#
#  Default data for new filerelease system
#

INSERT INTO frs_filetype VALUES ('2000','Binary .rpm');
INSERT INTO frs_filetype VALUES ('2100','Binary .deb');
INSERT INTO frs_filetype VALUES ('3000','Binary .zip');
INSERT INTO frs_filetype VALUES ('3001','Binary .bz2');
INSERT INTO frs_filetype VALUES ('3002','Binary .gz');
INSERT INTO frs_filetype VALUES ('3020','Binary .tar.gz, .tgz');
INSERT INTO frs_filetype VALUES ('3100','Binary .jar');
INSERT INTO frs_filetype VALUES ('3150','Binary installer');
INSERT INTO frs_filetype VALUES ('3900','Other Binary File');
INSERT INTO frs_filetype VALUES ('4000','Source .rpm');
INSERT INTO frs_filetype VALUES ('5000','Source .zip');
INSERT INTO frs_filetype VALUES ('5001','Source .bz2');
INSERT INTO frs_filetype VALUES ('5002','Source .gz');
INSERT INTO frs_filetype VALUES ('5020','Source .tar.gz, .tgz');
INSERT INTO frs_filetype VALUES ('5900','Other Source File');
INSERT INTO frs_filetype VALUES ('8000','.Documentation (any format)');
INSERT INTO frs_filetype VALUES ('8001','text');
INSERT INTO frs_filetype VALUES ('8002','html');
INSERT INTO frs_filetype VALUES ('8003','pdf');
INSERT INTO frs_filetype VALUES ('9999','Other');

INSERT INTO frs_processor VALUES ('1500','x86_64','15','100');
INSERT INTO frs_processor VALUES ('2000','ARMv7','20','100');
INSERT INTO frs_processor VALUES ('3000','ARMv8','30','100');
INSERT INTO frs_processor VALUES ('4000','RISC-V','40','100');
INSERT INTO frs_processor VALUES ('8000','Any','80','100');
INSERT INTO frs_processor VALUES ('9999','Other','90','100');

#
# Default data for Software Map
#


INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids, mandatory) VALUES (1, 2000031601, 0, 0, 'topic', 'Topic', 'Topic categorization.', 0, 0, 'Topic', '1', 0);
INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids, mandatory) VALUES (2, 2000031601, 0, 0, 'license', 'License', 'License terms under which the resource is distributed.', 0, 0, 'License', '2', 0);
INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids, mandatory) VALUES (3, 2000111301, 2, 2, 'osi', 'Open Source Approved license', 'Open Source approved licenses. Use one of these only if Open Sourcing your software has been explicitely approved by your legal department.', 0, 0, 'License :: Open Source Approved license', '2 :: 3', 0);

--
-- Dumping data for table 'service'
--

-- Group 100 (templates)
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (1, 100, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/$projectname/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (2, 100, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=$group_id', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (4, 100, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=$group_id', 0, 0, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (12, 100, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=$group_id', 0, 0, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (14, 100, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=$group_id', 1, 0, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (17, 100, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=$group_id', 0, 0, 'system', 105);

-- Group 1
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (31, 1, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/admin/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (32, 1, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=1', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (34, 1, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=1', 0, 0, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (38, 1, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=1', 0, 0, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (40, 1, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=1', 1, 0, 'system', 140);

-- Group 46 - SiteNews
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (51, 46, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/sitenews/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (52, 46, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (54, 46, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=46', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (55, 46, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=46', 1, 1, 'system', 120);

--
--
-- This service has the id 100 to force the next id to be greater than 100
-- 100 is a special value (None value)
--
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (100, 0, 'None', 'None', '', '', 0, 0, 'project', 0);


--
-- Insert special ugroup values
--
-- Apart from the mandatory 'nobody', 'anonymous_users', 'registered_users', 'project_members' and
-- 'project_admins', the table lists all possible roles in the 'User Permissions' matrix.
-- If you modify anything here, check also www/project/admin/ugroup_utils.php

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (100, "ugroup_nobody_name_key", "ugroup_nobody_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (1, "ugroup_anonymous_users_name_key", "ugroup_anonymous_users_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (2, "ugroup_registered_users_name_key", "ugroup_registered_users_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (3, "ugroup_project_members_name_key", "ugroup_project_members_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (4, "ugroup_project_admins_name_key", "ugroup_project_admins_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (5, "ugroup_authenticated_users_name_key", "ugroup_authenticated_users_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (11, "ugroup_file_manager_admin_name_key", "ugroup_file_manager_admin_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (14, "ugroup_wiki_admin_name_key", "ugroup_wiki_admin_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (16, "ugroup_forum_admin_name_key", "ugroup_forum_admin_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (17, "ugroup_news_admin_name_key", "ugroup_news_admin_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (18, "ugroup_news_writer_name_key", "ugroup_news_writer_desc_key", 100);




--
-- Set permissions_values entries. These should normally be set at installation time only.
-- ugroup_id should always be lower than 100.
--
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PACKAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',11);

-- No default value for RELEASE_READ -> use parent permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',11);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKI_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',14);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKIPAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',14);

-- Allow ugroup 'nobody'
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIATTACHMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIATTACHMENT_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKIATTACHMENT_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIATTACHMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIATTACHMENT_READ',4);

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('NEWS_READ',1,1);

-- install and enable pluginsadministration
INSERT INTO plugin (name, available) VALUES ('pluginsadministration', '1');

--
-- Insert default references
--

INSERT INTO reference SET \
    id='1',        \
    keyword='art', \
    description='Tracker Artifact', \
    link='/plugins/tracker/?&aid=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='plugin_tracker', \
    nature='plugin_tracker_artifact';

INSERT INTO reference SET \
    id='2',        \
    keyword='artifact', \
    description='Tracker Artifact', \
    link='/plugins/tracker/?&aid=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='plugin_tracker', \
    nature='plugin_tracker_artifact';

INSERT INTO reference SET \
    id='5',        \
    keyword='rev', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='svn', \
    nature='svn_revision';

INSERT INTO reference SET \
    id='6',        \
    keyword='revision', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='svn', \
    nature='svn_revision';

INSERT INTO reference SET \
    id='7',        \
    keyword='svn', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='svn', \
    nature='svn_revision';

INSERT INTO reference SET \
    id='8',        \
    keyword='wiki', \
    description='reference_wiki_desc_key', \
    link='/wiki/index.php?group_id=$group_id&pagename=$1', \
    scope='S', \
    service_short_name='wiki', \
    nature='wiki_page';

INSERT INTO reference SET \
    id='9',        \
    keyword='wiki', \
    description='reference_wikiversion_desc_key', \
    link='/wiki/index.php?group_id=$group_id&pagename=$1&version=$2', \
    scope='S', \
    service_short_name='wiki', \
    nature='wiki_page';

INSERT INTO reference SET \
    id='12',        \
    keyword='news', \
    description='reference_news_desc_key', \
    link='/forum/forum.php?forum_id=$1', \
    scope='S', \
    service_short_name='news', \
    nature='news';

INSERT INTO reference SET \
    id='13',        \
    keyword='forum', \
    description='reference_forum_desc_key', \
    link='/forum/forum.php?forum_id=$1', \
    scope='S', \
    service_short_name='forum', \
    nature='forum';

INSERT INTO reference SET \
    id='14',        \
    keyword='msg', \
    description='reference_msg_desc_key', \
    link='/forum/message.php?msg_id=$1', \
    scope='S', \
    service_short_name='forum', \
    nature='forum_message';

INSERT INTO reference SET \
    id='15',        \
    keyword='file', \
    description='reference_file_desc_key', \
    link='', \
    scope='S', \
    service_short_name='file', \
    nature='file';

INSERT INTO reference SET \
    id='16',        \
    keyword='release', \
    description='reference_release_desc_key', \
    link='/file/showfiles.php?group_id=$group_id&release_id=$1', \
    scope='S', \
    service_short_name='file', \
    nature='release';


-- Reserved references for plugins:

-- Docman plugin
--   10 doc
--   11 document
--   17 folder
--   18 dossier

-- Site-wide references

-- empty reference
INSERT INTO reference SET \
    id='100',        \
    keyword='', \
    description='Empty reference', \
    link='', \
    scope='S', \
    service_short_name='', \
    nature='other';



--
-- Add references to existing projects
--


-- Template project (group 100)
INSERT INTO reference_group SET reference_id='1', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='2', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='3', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='4', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='5', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='6', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='7', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='8', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='9', group_id='100', is_active='1';
-- INSERT INTO reference_group SET reference_id='10', group_id='100', is_active='1';
-- INSERT INTO reference_group SET reference_id='11', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='12', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='15', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='16', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='70', group_id='100', is_active='1';

-- Admin project (group 1)
INSERT INTO reference_group SET reference_id='1', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='2', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='3', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='4', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='5', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='6', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='7', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='8', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='9', group_id='1', is_active='1';
-- INSERT INTO reference_group SET reference_id='10', group_id='1', is_active='1';
-- INSERT INTO reference_group SET reference_id='11', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='12', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='15', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='16', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='70', group_id='1', is_active='1';

-- Sitenews project (group 46)
INSERT INTO reference_group SET reference_id='12', group_id='46', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='46', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='46', is_active='1';


--
-- Add forums in Template project (group 100)
--
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Open Discussion',1 ,'General Discussion');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Help',1 ,'Get Help');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Developers',0 ,'Project Developer Discussion');

INSERT INTO system_events_followers (emails, types) VALUES ('admin', 'WARNING,ERROR');

INSERT INTO forgeconfig (name, value) VALUES ('access_mode', 'anonymous');
INSERT INTO forgeconfig (name, value) VALUES ('sys_project_approval', '0');
INSERT INTO forgeconfig (name, value) VALUES ('sys_user_approval', '0');
INSERT INTO forgeconfig (name, value) VALUES ('display_homepage_statistics', '1');
INSERT INTO forgeconfig (name, value) VALUES ('display_homepage_news', '1');
INSERT INTO forgeconfig (name, value) VALUES ('display_tuleap_review_link', '1');
INSERT INTO forgeconfig (name, value) VALUES ('default_project_visibility', 'public');
INSERT INTO forgeconfig (name, value) VALUES ('sys_suspend_inactive_accounts_notification_delay', '0');
INSERT INTO forgeconfig (name, value) VALUES ('enable_not_mandatory_description', '1');
INSERT INTO forgeconfig (name, value) VALUES ('force_new_project_creation_usage', '1');
INSERT INTO forgeconfig (name, value) VALUES ('can_use_default_site_template', '0');
INSERT INTO forgeconfig (name, value) VALUES ('sys_suspend_send_account_suspension_email', '0');
INSERT INTO forgeconfig (name, value) VALUES ('are_unix_users_disabled', 1);

INSERT INTO password_configuration (breached_password_enabled) VALUES (1);

--
-- Email gateway salt
--
INSERT INTO email_gateway_salt (salt)
    VALUES (SHA1(UUID()))
;


--
-- SVN default cache parameters
--
INSERT INTO svn_cache_parameter VALUES ('lifetime', '5');

--
-- Default dashboards
--
INSERT INTO project_dashboards (id, project_id, name) VALUES (1, 100, 'Dashboard');
INSERT INTO dashboards_lines (id, dashboard_id, dashboard_type, layout, `rank`) VALUES (1, 1, 'project', 'two-columns-small-big', 0);
INSERT INTO dashboards_lines_columns (id, line_id, `rank`) VALUES (1, 1, 0);
INSERT INTO dashboards_lines_columns_widgets (id, column_id, `rank`, name, content_id) VALUES (1, 1, 0, 'projectdescription', 0);
INSERT INTO dashboards_lines_columns (id, line_id, `rank`) VALUES (2, 1, 1);
INSERT INTO dashboards_lines_columns_widgets (id, column_id, `rank`, name, content_id) VALUES (2, 2, 0, 'projectheartbeat', 0);

INSERT INTO user_dashboards (id, user_id, name) VALUES (1, 101, 'My Dashboard');
INSERT INTO dashboards_lines (id, dashboard_id, dashboard_type, layout, `rank`) VALUES (2, 1, 'user', 'two-columns', 0);
INSERT INTO dashboards_lines_columns (id, line_id, `rank`) VALUES (3, 2, 0);
INSERT INTO dashboards_lines_columns_widgets (id, column_id, `rank`, name, content_id) VALUES (3, 3, 0, 'mywelcomemessage', 0);
INSERT INTO dashboards_lines_columns (id, line_id, `rank`) VALUES (4, 2, 1);
INSERT INTO dashboards_lines_columns_widgets (id, column_id, `rank`, name, content_id) VALUES (4, 4, 0, 'myadmin', 0);
INSERT INTO dashboards_lines_columns_widgets (id, column_id, `rank`, name, content_id) VALUES (5, 4, 1, 'mysystemevent', 0);
