#
# Copyright (c) Enalean 2014-2016. All rights reserved
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
        user_id = 100, \
        user_name = 'None', \
        email = 'noreply@_DOMAIN_NAME_', \
        user_pw = '*********34343', \
        realname = '0', \
        register_purpose = NULL, \
        status = 'S', \
        shell = '0', \
        unix_pw = '0', \
        unix_status = '0', \
        unix_uid = 0, \
        unix_box = '0', \
        ldap_id = NULL, \
        add_date = 940000000, \
        confirm_hash = NULL, \
        mail_siteupdates = 0, \
        mail_va = 0, \
        sticky_login = 0, \
        authorized_keys = NULL, \
        email_new = NULL, \
        timezone = 'GMT', \
        theme = '', \
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
        user_pw = '', \
        realname = 'Site Administrator', \
        register_purpose = NULL, \
        status = 'A', \
        shell = '/bin/false', \
        unix_pw = 'SITEADMIN_UNIX_PASSWORD', \
        unix_status = 'A', \
        unix_uid = 1, \
        unix_box = 'shell1', \
        ldap_id = NULL, \
        add_date = 940000000, \
        confirm_hash = NULL, \
        mail_siteupdates = 0, \
        mail_va = 0, \
        sticky_login = 0, \
        authorized_keys = NULL, \
        email_new = NULL, \
        timezone = 'GMT', \
        theme = '', \
        language_id = 'en_US', \
        last_pwd_update = '0';
        
INSERT INTO user_access SET \
        user_id = 101, \
        last_access_date = '0';
        

#
# Default Data for 'group_desc'
#
INSERT INTO group_desc SET \
		group_desc_id='101' , \
		desc_required='1' , \
		desc_name='project_desc_name:full_desc' , \
		desc_description='project_desc_desc:full_desc' , \
		desc_rank='10' , \
		desc_type='text';
		
INSERT INTO group_desc SET \
		group_desc_id='104' , \
		desc_required='0' , \
		desc_name='project_desc_name:other_comments' , \
		desc_description='project_desc_desc:other_comments' , \
		desc_rank='100' , \
		desc_type='text';

#
# Default Data for 'groups'
#
# Note: if you change the 'group' structure, do not forget to update the IM group (no 47) in the IM plugin.

INSERT INTO groups SET \
  group_id = '1', \
  group_name = 'Administration Project', \
  access = 'public', \
  status = 'A', \
  unix_group_name = 'admin', \
  unix_box = 'shell1', \
  http_domain = 'admin._DOMAIN_NAME_', \
  short_description = 'Administration Project', \
  cvs_box = 'cvs1', \
  svn_box = 'svn1', \
  register_time = 940000000, \
  rand_hash = '', \
  new_bug_address = 'codendi-admin@_DOMAIN_NAME_', \
  new_patch_address = 'codendi-admin@_DOMAIN_NAME_', \
  new_support_address = 'codendi-admin@_DOMAIN_NAME_', \
  type = '1', \
  send_all_bugs = '1', \
  send_all_patches = '1', \
  send_all_support = '1', \
  bug_preamble = '', \
  support_preamble = '', \
  patch_preamble = '', \
  pm_preamble = '', \
  xrx_export_ettm = '0', \
  built_from_template = '100', \
  bug_allow_anon = '1', \
  cvs_tracker = '1', \
  cvs_events_mailing_list = '', \
  cvs_events_mailing_header = '', \
  cvs_preamble = '', \
  svn_tracker = '1', \
  svn_mandatory_ref = '0', \
  svn_events_mailing_header = '', \
  svn_preamble = '', \
  svn_commit_to_tag_denied ='0' ;

INSERT INTO groups SET \
  group_id = '46', \
  group_name = 'Site News', \
  access = 'private', \
  status = 'A', \
  unix_group_name = 'sitenews', \
  unix_box = 'shell1', \
  http_domain = 'sitenews._DOMAIN_NAME_', \
  short_description = 'Site News Private Project. All Site News should be posted from this project', \
  cvs_box = 'cvs1', \
  svn_box = 'svn1', \
  register_time = 940000000, \
  rand_hash = '', \
  new_bug_address = '', \
  new_patch_address = '', \
  new_support_address = '', \
  type = '1', \
  send_all_bugs = '0', \
  send_all_patches = '0', \
  send_all_support = '0', \
  bug_preamble = '', \
  support_preamble = '', \
  patch_preamble = '', \
  pm_preamble = '', \
  xrx_export_ettm = '0', \
  built_from_template = '100', \
  bug_allow_anon = '1', \
  cvs_tracker = '0', \
  cvs_events_mailing_list = '', \
  cvs_events_mailing_header = '', \
  cvs_preamble = '', \
  svn_tracker = '0', \
  svn_mandatory_ref = '0', \
  svn_events_mailing_header = '', \
  svn_preamble = '', \
  svn_commit_to_tag_denied ='0' ;


INSERT INTO groups SET \
  group_id = '100', \
  group_name = 'Default Site Template', \
  access = 'private', \
  status = 's', \
  unix_group_name = 'none', \
  unix_box = 'shell1', \
  http_domain = '', \
  short_description = 'The default Tuleap template', \
  cvs_box = '', \
  svn_box = '', \
  register_time = 940000000, \
  rand_hash = '', \
  new_bug_address = '', \
  new_patch_address = '', \
  new_support_address = '', \
  type = '2', \
  send_all_bugs = '0', \
  send_all_patches = '0', \
  send_all_support = '0', \
  bug_preamble = '', \
  support_preamble = '', \
  patch_preamble = '', \
  pm_preamble = '', \
  xrx_export_ettm = '0', \
  built_from_template = '100', \
  bug_allow_anon = '1', \
  cvs_tracker = '1', \
  cvs_events_mailing_list = '', \
  cvs_events_mailing_header = '', \
  cvs_preamble = '', \
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
# flags after 'A' are: bug,forum,project,patch,support,doc,file,wiki,svn,news

INSERT INTO user_group VALUES (1, 101, 1, 'A', 2, 2, 2, 2, 2, 1, 2, 2, 2, 2);

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

INSERT INTO frs_processor VALUES ('1000','i386','10','100');
INSERT INTO frs_processor VALUES ('1500','x86_64','15','100');
INSERT INTO frs_processor VALUES ('2000','PPC','20','100');
INSERT INTO frs_processor VALUES ('3000','MIPS','30','100');
INSERT INTO frs_processor VALUES ('4000','Sparc','40','100');
INSERT INTO frs_processor VALUES ('5000','UltraSparc','50','100');
INSERT INTO frs_processor VALUES ('6000','IA64','60','100');
INSERT INTO frs_processor VALUES ('7000','Alpha','70','100');
INSERT INTO frs_processor VALUES ('8000','Any','80','100');
INSERT INTO frs_processor VALUES ('9999','Other','90','100');

#
# Default data for Software Map
#
    

INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids, mandatory) VALUES (1, 2000031601, 0, 0, 'topic', 'Topic', 'Topic categorization.', 0, 0, 'Topic', '1', 0);
INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids, mandatory) VALUES (2, 2000031601, 0, 0, 'license', 'License', 'License terms under which the resource is distributed.', 0, 0, 'License', '2', 0);
INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids, mandatory) VALUES (3, 2000111301, 2, 2, 'osi', 'Open Source Approved license', 'Open Source approved licenses. Use one of these only if Open Sourcing your software has been explicitely approved by your legal department.', 0, 0, 'License :: Open Source Approved license', '2 :: 3', 0);
    
#
# Snippet category table
#

INSERT INTO snippet_category VALUES (100,'None');
INSERT INTO snippet_category VALUES (1,'UNIX Admin');
INSERT INTO snippet_category VALUES (2,'HTML Manipulation');
INSERT INTO snippet_category VALUES (3,'Text Processing');
INSERT INTO snippet_category VALUES (4,'Print Processing');
INSERT INTO snippet_category VALUES (5,'Calendars');
INSERT INTO snippet_category VALUES (6,'Database');
INSERT INTO snippet_category VALUES (7,'Data Structure Manipulation');
INSERT INTO snippet_category VALUES (8,'File Management');
INSERT INTO snippet_category VALUES (9,'Scientific Computation');
INSERT INTO snippet_category VALUES (10,'Office Utilities');
INSERT INTO snippet_category VALUES (11,'User Interface');
INSERT INTO snippet_category VALUES (12,'Other');
INSERT INTO snippet_category VALUES (13,'Network');
INSERT INTO snippet_category VALUES (14,'Data Acquisition and Control');


#
# snippet type table
#

INSERT INTO snippet_type VALUES (100,'None');
INSERT INTO snippet_type VALUES (1,'Function');
INSERT INTO snippet_type VALUES (2,'Full Script');
INSERT INTO snippet_type VALUES (3,'Sample Code (HOWTO)');
INSERT INTO snippet_type VALUES (4,'README');
INSERT INTO snippet_type VALUES (5,'Class');
INSERT INTO snippet_type VALUES (6,'Full Program');
INSERT INTO snippet_type VALUES (7,'Macros');


#
# snippet license table
#

INSERT INTO snippet_license VALUES (100,'None');
INSERT INTO snippet_license VALUES (1,'Tuleap exchange Policy');
INSERT INTO snippet_license VALUES (2,'Other');


#
# snippet language table
#

INSERT INTO snippet_language VALUES (100,'None');
INSERT INTO snippet_language VALUES (1,'Awk');
INSERT INTO snippet_language VALUES (2,'C');
INSERT INTO snippet_language VALUES (3,'C++');
INSERT INTO snippet_language VALUES (4,'Perl');
INSERT INTO snippet_language VALUES (5,'PHP');
INSERT INTO snippet_language VALUES (6,'Python');
INSERT INTO snippet_language VALUES (7,'Unix Shell');
INSERT INTO snippet_language VALUES (8,'Java');
INSERT INTO snippet_language VALUES (9,'AppleScript');
INSERT INTO snippet_language VALUES (10,'Visual Basic');
INSERT INTO snippet_language VALUES (11,'TCL');
INSERT INTO snippet_language VALUES (12,'Lisp');
INSERT INTO snippet_language VALUES (13,'Mixed');
INSERT INTO snippet_language VALUES (14,'JavaScript');
INSERT INTO snippet_language VALUES (15,'SQL');
INSERT INTO snippet_language VALUES (16,'MatLab');
INSERT INTO snippet_language VALUES (17,'Other Language');
INSERT INTO snippet_language VALUES (18,'LabView');
INSERT INTO snippet_language VALUES (19,'C#');
INSERT INTO snippet_language VALUES (20,'Postscript');
INSERT INTO snippet_language VALUES (21,'VB.NET');


--
-- Dumping data for table 'service'
--

-- Group 100 (templates)
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (1, 100, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/$projectname/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (2, 100, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=$group_id', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (3, 100, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://$projectname.$sys_default_domain', 1, 1, 'system', 30);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (4, 100, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=$group_id', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (8, 100, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=$group_id', 1, 1, 'system', 80);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (12, 100, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=$group_id', 1, 1, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (13, 100, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=$group_id', 1, 0, 'system', 130);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (14, 100, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=$group_id', 1, 1, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (16, 100, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=$group_id', 1, 1, 'system', 135);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (17, 100, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=$group_id', 1, 1, 'system', 105);

-- Group 1
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (31, 1, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/admin/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (32, 1, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=1', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (33, 1, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://admin._DOMAIN_NAME_', 1, 0, 'system', 30);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (34, 1, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=1', 1, 0, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (35, 1, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=1', 1, 0, 'system', 80);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (38, 1, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=1', 1, 0, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (39, 1, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=1', 1, 0, 'system', 130);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (40, 1, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=1', 1, 0, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (42, 1, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=1', 1, 0, 'system', 135);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (43, 1, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=1', 1, 0, 'system', 105);

-- Group 46 - SiteNews
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (51, 46, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/sitenews/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (52, 46, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (54, 46, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=46', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (55, 46, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=46', 1, 1, 'system', 120);

--
--
-- This service has the id 100 to force the next id to be greater than 100
-- 100 is a special value (None value)
--
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (100, 0, 'None', 'None', '', '', 0, 0, 'project', 0);


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
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (12, "ugroup_document_tech_name_key", "ugroup_document_tech_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (13, "ugroup_document_admin_name_key", "ugroup_document_admin_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (14, "ugroup_wiki_admin_name_key", "ugroup_wiki_admin_desc_key", 100);

-- Not used yet
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (17, "forum_moderator", "Forum Moderators", 100);
--



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

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('DOCGROUP_READ',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',12);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',13);

-- No default value for DOCUMENT_READ -> use parent permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',12);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',13);

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
    id='3',        \
    keyword='commit', \
    description='reference_cvs_desc_key', \
    link='/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='cvs', \
    nature='cvs_commit';

INSERT INTO reference SET \
    id='4',        \
    keyword='cvs', \
    description='reference_cvs_desc_key', \
    link='/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='cvs', \
    nature='cvs_commit';

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
    link='/file/confirm_download.php?group_id=$group_id&file_id=$1', \
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

INSERT INTO reference SET \
    id='70',        \
    keyword='snippet', \
    description='reference_snippet_desc_key', \
    link='/snippet/detail.php?type=snippet&id=$1', \
    scope='S', \
    nature='snippet';

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

-- 
-- Layouts
-- 
INSERT INTO layouts (id, name, description, scope) VALUES 
(1, '2 columns', 'Simple layout made of 2 columns', 'S'),
(2, '3 columns', 'Simple layout made of 3 columns', 'S'),
(3, 'Left', 'Simple layout made of a main column and a small, left sided, column', 'S'),
(4, 'Right', 'Simple layout made of a main column and a small, right sided, column', 'S');

INSERT INTO layouts_rows (id, layout_id, rank) VALUES (1, 1, 0), (2, 2, 0),(3, 3, 0), (4, 4, 0);
INSERT INTO layouts_rows_columns (id, layout_row_id, width) VALUES (1, 1, 50), (2, 1, 50), (3, 2, 33), (4, 2, 33), (5, 2, 33), (6, 3, 33), (7, 3, 66), (8, 4, 66), (9, 4, 33);

-- Users

INSERT INTO owner_layouts (owner_id, owner_type, layout_id, is_default) 
SELECT user_id, 'u', 1, 1 
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'myprojects', 0
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mybookmarks', 1
FROM user;

-- Add mydocman only if docman is installed
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mydocman', 2
FROM user, plugin
WHERE plugin.name = 'docman';

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mymonitoredforums', 3
FROM user;

-- Add myadmin only to current admins
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT user_id, 'u', 1, 2, 'myadmin', -2
FROM user_group
WHERE group_id = 1
  AND admin_flags = 'A';

-- Add mysystemevent only to current admins
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT DISTINCT user_id, 'u', 1, 2, 'mysystemevent', -3
FROM user_group
WHERE group_id = 1
  AND admin_flags = 'A';

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 2, 'mymonitoredfp', 1
FROM user;



-- Projects

INSERT INTO owner_layouts (owner_id, owner_type, layout_id, is_default) 
SELECT group_id, 'g', 1, 1 
FROM groups;

-- First column
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 1, 'projectdescription', 0
FROM groups;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 1, 'projectclassification', 1
FROM groups;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 1, 'projectpublicareas', 2
FROM groups;

-- Second column
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectmembers', 0
FROM groups;

-- only if News is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestnews', 1
FROM service
WHERE short_name = 'news' AND is_active = 1 AND is_used = 1;

-- only if FRS is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestfilereleases', 2
FROM service
WHERE short_name = 'file' AND is_active = 1 AND is_used = 1;

-- only if SVN is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestsvncommits', 3
FROM service
WHERE short_name = 'svn' AND is_active = 1 AND is_used = 1;

-- only if CVS is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT group_id, 'g', 1, 2, 'projectlatestcvscommits', 4
FROM service
WHERE short_name = 'cvs' AND is_active = 1 AND is_used = 1;

INSERT INTO system_events_followers (emails, types) VALUES ('admin', 'WARNING,ERROR');

INSERT INTO homepage (use_standard_homepage) VALUES (1);

INSERT INTO homepage_headline (language_id, headline) VALUES
('en_US', 'Tuleap helps teams to deliver awesome applications, better, faster, and easier.
Here you plan, track, code, and collaborate on software projects.'),
('fr_FR', 'Avec Tuleap, les équipes livrent les applications plus rapidement, plus efficacement et de meilleure qualité.
Venez planifier, suivre, développer & collaborer sur vos projets logiciels.');

INSERT INTO forgeconfig (name, value) VALUES ('access_mode', 'anonymous');

--
-- Email gateway salt
--
INSERT INTO email_gateway_salt (salt)
    VALUES (SHA1(UUID()))
;


--
-- SVN default cache parameters
--
INSERT INTO svn_cache_parameter VALUES ('maximum_credentials' , '10'), ('lifetime', '5');
#
# EOF
#
