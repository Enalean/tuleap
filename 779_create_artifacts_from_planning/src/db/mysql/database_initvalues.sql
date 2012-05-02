# 
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# 
# This file is a part of Codendi.
# 
# Codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# Codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Codendi. If not, see <http://www.gnu.org/licenses/>.
# 

#
# Purpose:
#    Insert default values in all the Codendi tables.



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
        people_view_skills = 0, \
        people_resume = '', \
        timezone = 'GMT', \
        fontsize = 0, \
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
        user_pw = '6f3cac6213ffceee27cc85414f458caa', \
        realname = 'Site Administrator', \
        register_purpose = NULL, \
        status = 'A', \
        shell = '/bin/false', \
        unix_pw = '$1$Sn;W@$PXu/wJEYCCN2.BmF2uSfT/', \
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
        people_view_skills = 0, \
        people_resume = '', \
        timezone = 'GMT', \
        fontsize = 0, \
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
  is_public = '1', \
  status = 'A', \
  unix_group_name = 'admin', \
  unix_box = 'shell1', \
  http_domain = 'admin._DOMAIN_NAME_', \
  short_description = 'Administration Project', \
  cvs_box = 'cvs1', \
  svn_box = 'svn1', \
  license = 'cdi', \
  license_other = '', \
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
  svn_preamble = '' ;

INSERT INTO groups SET \
  group_id = '46', \
  group_name = 'Site News', \
  is_public = '0', \
  status = 'A', \
  unix_group_name = 'sitenews', \
  unix_box = 'shell1', \
  http_domain = 'sitenews._DOMAIN_NAME_', \
  short_description = 'Site News Private Project. All Site News should be posted from this project', \
  cvs_box = 'cvs1', \
  svn_box = 'svn1', \
  license = 'cdi', \
  license_other = '', \
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
  svn_preamble = '' ;


INSERT INTO groups SET \
  group_id = '100', \
  group_name = 'Default Site Template', \
  is_public = '0', \
  status = 's', \
  unix_group_name = 'none', \
  unix_box = 'shell1', \
  http_domain = '', \
  short_description = 'The default Tuleap template', \
  cvs_box = '', \
  svn_box = '', \
  license = '', \
  license_other = '', \
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
  svn_preamble = '' ;


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
# Default data for Help Wanted System
#

INSERT INTO people_skill VALUES ('','3100 SQL');
INSERT INTO people_skill VALUES ('','3110 C/C++');
INSERT INTO people_skill VALUES ('','3120 Perl');
INSERT INTO people_skill VALUES ('','3130 PHP');
INSERT INTO people_skill VALUES ('','3140 Java');
INSERT INTO people_skill VALUES ('','3900 Other Prog. Lang.');
 
INSERT INTO people_skill VALUES ('','5100 Chinese');
INSERT INTO people_skill VALUES ('','5110 Japanese');
INSERT INTO people_skill VALUES ('','5120 Spanish');
INSERT INTO people_skill VALUES ('','5130 French');
INSERT INTO people_skill VALUES ('','5140 German');
INSERT INTO people_skill VALUES ('','5900 Other Spoken Lang.');
 
INSERT INTO people_skill VALUES ('','7100 UNIX Admin');
INSERT INTO people_skill VALUES ('','7110 Networking');
INSERT INTO people_skill VALUES ('','7120 Security');
INSERT INTO people_skill VALUES ('','7130 Writing');
INSERT INTO people_skill VALUES ('','7140 Editing');
INSERT INTO people_skill VALUES ('','7150 Databases');
INSERT INTO people_skill VALUES ('','7900 Other Skill Area');

INSERT INTO people_skill_year VALUES ('','< 6 Months');
INSERT INTO people_skill_year VALUES ('','6 Mo - 2 yr');
INSERT INTO people_skill_year VALUES ('','2 yr - 5 yr');
INSERT INTO people_skill_year VALUES ('','5 yr - 10 yr');
INSERT INTO people_skill_year VALUES ('','> 10 years');

INSERT INTO people_skill_level VALUES ('10','Want to Learn');
INSERT INTO people_skill_level VALUES ('20','Familiar');
INSERT INTO people_skill_level VALUES ('30','Competent');
INSERT INTO people_skill_level VALUES ('40','Wizard');
INSERT INTO people_skill_level VALUES ('50','Wrote The Book');
INSERT INTO people_skill_level VALUES ('60','Wrote It');

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
# Default Data for Survey Manager
#

INSERT INTO survey_question_types (id, type, rank) VALUES (1,'radio_buttons_1_5', '21');
INSERT INTO survey_question_types (id, type, rank) VALUES (2,'text_area', '30');
INSERT INTO survey_question_types (id, type, rank) VALUES (3,'radio_buttons_yes_no', '22');
INSERT INTO survey_question_types (id, type, rank) VALUES (4,'comment_only', '10');
INSERT INTO survey_question_types (id, type, rank) VALUES (5,'text_field', '31');
INSERT INTO survey_question_types (id, type, rank) VALUES (6,'radio_buttons', '20');
INSERT INTO survey_question_types (id, type, rank) VALUES (7,'select_box', '23');
INSERT INTO survey_question_types (id, type, rank) VALUES (100,'none', '40');

#
# Developer survey attached to project #1, default status is inactive
#
INSERT INTO surveys (survey_id, group_id, survey_title, is_active) VALUES (1,1,'dev_survey_title_key',0);
 

#
# Default data for Software Map
#
    

INSERT INTO trove_cat VALUES (1, 2000031601, 0, 0, 'audience', 'Intended Audience', 'The main class of people likely to be interested in this resource.', 0, 0, 'Intended Audience', '1');
INSERT INTO trove_cat VALUES (2, 2000032401, 1, 1, 'endusers', 'End Users/Desktop', 'Programs and resources for software end users. Software for the desktop.', 0, 0, 'Intended Audience :: End Users/Desktop', '1 :: 2');
INSERT INTO trove_cat VALUES (3, 2000041101, 1, 1, 'developers', 'Developers', 'Programs and resources for software developers, to include libraries.', 0, 0, 'Intended Audience :: Developers', '1 :: 3');
INSERT INTO trove_cat VALUES (4, 2000031601, 1, 1, 'sysadmins', 'System Administrators', 'Programs and resources for people who administer computers and networks.', 0, 0, 'Intended Audience :: System Administrators', '1 :: 4');
INSERT INTO trove_cat VALUES (5, 2000040701, 1, 1, 'other', 'Other Audience', 'Programs and resources for an unlisted audience.', 0, 0, 'Intended Audience :: Other Audience', '1 :: 5');
INSERT INTO trove_cat VALUES (6, 2000031601, 0, 0, 'developmentstatus', 'Development Status', 'An indication of the development status of the software or resource.', 0, 0, 'Development Status', '6');
INSERT INTO trove_cat VALUES (7, 2000040701, 6, 6, 'planning', '1 - Planning', 'This resource is in the planning stages only. There is no code.', 0, 0, 'Development Status :: 1 - Planning', '6 :: 7');
INSERT INTO trove_cat VALUES (8, 2000040701, 6, 6, 'prealpha', '2 - Pre-Alpha', 'There is code for this project, but it is not usable except for further development.', 0, 0, 'Development Status :: 2 - Pre-Alpha', '6 :: 8');
INSERT INTO trove_cat VALUES (9, 2000041101, 6, 6, 'alpha', '3 - Alpha', 'Resource is in early development, and probably incomplete and/or extremely buggy.', 0, 0, 'Development Status :: 3 - Alpha', '6 :: 9');
INSERT INTO trove_cat VALUES (10, 2000040701, 6, 6, 'beta', '4 - Beta', 'Resource is in late phases of development. Deliverables are essentially complete, but may still have significant bugs.', 0, 0, 'Development Status :: 4 - Beta', '6 :: 10');
INSERT INTO trove_cat VALUES (11, 2000040701, 6, 6, 'production', '5 - Production/Stable', 'Deliverables are complete and usable by the intended audience.', 0, 0, 'Development Status :: 5 - Production/Stable', '6 :: 11');
INSERT INTO trove_cat VALUES (12, 2000040701, 6, 6, 'mature', '6 - Mature', 'This resource has an extensive history of successful use and has probably undergone several stable revisions.', 0, 0, 'Development Status :: 6 - Mature', '6 :: 12');
INSERT INTO trove_cat VALUES (13, 2000031601, 0, 0, 'license', 'License', 'License terms under which the resource is distributed.', 0, 0, 'License', '13');
INSERT INTO trove_cat VALUES (14, 2000111301, 13, 13, 'cdi', 'Tuleap exchange Policy', 'The default Policy ruling the code sharing attitude.', 0, 0, 'License :: Tuleap exchange Policy', '13 :: 14');
INSERT INTO trove_cat VALUES (274, 2001061501, 154, 18, 'printservices', 'Print Services', 'XAC/DDA/Print Services Projects', 0, 0, 'Topic :: Printing :: Print Services', '18 :: 154 :: 274');
INSERT INTO trove_cat VALUES (275, 2001062601, 160, 160, 'JSP', 'JSP', 'Java Server Pages: Sun\'s Java language embedded in HTML pages', 0, 0, 'Programming Language :: JSP', '160 :: 275');
INSERT INTO trove_cat VALUES (18, 2000031601, 0, 0, 'topic', 'Topic', 'Topic categorization.', 0, 0, 'Topic', '18');
INSERT INTO trove_cat VALUES (20, 2000111301, 18, 18, 'communications', 'Internet/Intranet Connectivity', 'Protocols, Languages, Applications intended to facilitate communication between people nad/or computers', 0, 0, 'Topic :: Internet/Intranet Connectivity', '18 :: 20');
INSERT INTO trove_cat VALUES (22, 2000111301, 18, 18, 'docmgt', 'Document Management', 'All document related software (e.g. Doct services, repository, design/creation, encoding like Glyph or Barcode, formatting and document output like printing)', 0, 0, 'Topic :: Document Management', '18 :: 22');
INSERT INTO trove_cat VALUES (37, 2000111301, 20, 18, 'wireless', 'Wireless Communication', 'Tools supporting wireless communication (radio, IR,...)', 0, 0, 'Topic :: Internet/Intranet Connectivity :: Wireless Communication', '18 :: 20 :: 37');
INSERT INTO trove_cat VALUES (43, 2000111301, 18, 18, 'imagemgt', 'Image Management', 'Software to help capture, manipulate, transform, render images (e.g. image processing, color management, printing/marking, image capture, image compression technics, etc.)', 0, 0, 'Topic :: Image Management', '18 :: 43');
INSERT INTO trove_cat VALUES (45, 2000111301, 18, 18, 'development', 'Software Development', 'Software used to aid software development ( e.g. language interpreters, compilers, debuggers, project management tools, build tools, Devt Environment, Devt Framework,etc.)', 0, 0, 'Topic :: Software Development', '18 :: 45');
INSERT INTO trove_cat VALUES (97, 2000111301, 18, 18, 'scientific', 'Scientific/Engineering', 'Scientific or Engineering applications, to include research on non computer related sciences. (e.g. Physics and Mathematics in general, Data Visualization Tools, etc.)', 0, 0, 'Topic :: Scientific/Engineering', '18 :: 97');
INSERT INTO trove_cat VALUES (132, 2000111301, 18, 18, 'it', 'Information Technology', 'Applications related to information management and computer science in general (User Interface, Distributed Systems, Knowledge Mgt, Information Retrieval, Natural Language Processing, Security, Globalisation, etc.)', 0, 0, 'Topic :: Information Technology', '18 :: 132');
INSERT INTO trove_cat VALUES (136, 2000111301, 18, 18, 'system', 'System', 'Operating system core and administration utilities (e.g Drivers, Printers drivers, Emulators, Networking, Kernels, File Systems, Clustering, Benchmark, etc...', 0, 0, 'Topic :: System', '18 :: 136');
INSERT INTO trove_cat VALUES (154, 2000032001, 18, 18, 'printing', 'Printing', 'Tools, daemons, and utilities for printer control.', 0, 0, 'Topic :: Printing', '18 :: 154');
INSERT INTO trove_cat VALUES (160, 2000032001, 0, 0, 'language', 'Programming Language', 'Language in which this program was written, or was meant to support.', 0, 0, 'Programming Language', '160');
INSERT INTO trove_cat VALUES (161, 2000032001, 160, 160, 'apl', 'APL', 'APL', 0, 0, 'Programming Language :: APL', '160 :: 161');
INSERT INTO trove_cat VALUES (164, 2000032001, 160, 160, 'c', 'C', 'C', 0, 0, 'Programming Language :: C', '160 :: 164');
INSERT INTO trove_cat VALUES (162, 2000032001, 160, 160, 'assembly', 'Assembly', 'Assembly-level programs. Platform specific.', 0, 0, 'Programming Language :: Assembly', '160 :: 162');
INSERT INTO trove_cat VALUES (163, 2000051001, 160, 160, 'ada', 'Ada', 'Ada', 0, 0, 'Programming Language :: Ada', '160 :: 163');
INSERT INTO trove_cat VALUES (165, 2000032001, 160, 160, 'cpp', 'C++', 'C++', 0, 0, 'Programming Language :: C++', '160 :: 165');
INSERT INTO trove_cat VALUES (166, 2000032401, 160, 160, 'eiffel', 'Eiffel', 'Eiffel', 0, 0, 'Programming Language :: Eiffel', '160 :: 166');
INSERT INTO trove_cat VALUES (167, 2000032001, 160, 160, 'euler', 'Euler', 'Euler', 0, 0, 'Programming Language :: Euler', '160 :: 167');
INSERT INTO trove_cat VALUES (168, 2000032001, 160, 160, 'forth', 'Forth', 'Forth', 0, 0, 'Programming Language :: Forth', '160 :: 168');
INSERT INTO trove_cat VALUES (169, 2000032001, 160, 160, 'fortran', 'Fortran', 'Fortran', 0, 0, 'Programming Language :: Fortran', '160 :: 169');
INSERT INTO trove_cat VALUES (170, 2000032001, 160, 160, 'lisp', 'Lisp', 'Lisp', 0, 0, 'Programming Language :: Lisp', '160 :: 170');
INSERT INTO trove_cat VALUES (171, 2000041101, 160, 160, 'logo', 'Logo', 'Logo', 0, 0, 'Programming Language :: Logo', '160 :: 171');
INSERT INTO trove_cat VALUES (172, 2000032001, 160, 160, 'ml', 'ML', 'ML', 0, 0, 'Programming Language :: ML', '160 :: 172');
INSERT INTO trove_cat VALUES (173, 2000032001, 160, 160, 'modula', 'Modula', 'Modula-2 or Modula-3', 0, 0, 'Programming Language :: Modula', '160 :: 173');
INSERT INTO trove_cat VALUES (174, 2000032001, 160, 160, 'objectivec', 'Objective C', 'Objective C', 0, 0, 'Programming Language :: Objective C', '160 :: 174');
INSERT INTO trove_cat VALUES (175, 2000032001, 160, 160, 'pascal', 'Pascal', 'Pascal', 0, 0, 'Programming Language :: Pascal', '160 :: 175');
INSERT INTO trove_cat VALUES (176, 2000032001, 160, 160, 'perl', 'Perl', 'Perl', 0, 0, 'Programming Language :: Perl', '160 :: 176');
INSERT INTO trove_cat VALUES (177, 2000032001, 160, 160, 'prolog', 'Prolog', 'Prolog', 0, 0, 'Programming Language :: Prolog', '160 :: 177');
INSERT INTO trove_cat VALUES (178, 2000032001, 160, 160, 'python', 'Python', 'Python', 0, 0, 'Programming Language :: Python', '160 :: 178');
INSERT INTO trove_cat VALUES (179, 2000032001, 160, 160, 'rexx', 'Rexx', 'Rexx', 0, 0, 'Programming Language :: Rexx', '160 :: 179');
INSERT INTO trove_cat VALUES (180, 2000032001, 160, 160, 'simula', 'Simula', 'Simula', 0, 0, 'Programming Language :: Simula', '160 :: 180');
INSERT INTO trove_cat VALUES (181, 2000032001, 160, 160, 'smalltalk', 'Smalltalk', 'Smalltalk', 0, 0, 'Programming Language :: Smalltalk', '160 :: 181');
INSERT INTO trove_cat VALUES (182, 2000032001, 160, 160, 'tcl', 'Tcl', 'Tcl', 0, 0, 'Programming Language :: Tcl', '160 :: 182');
INSERT INTO trove_cat VALUES (183, 2000032001, 160, 160, 'php', 'PHP', 'PHP', 0, 0, 'Programming Language :: PHP', '160 :: 183');
INSERT INTO trove_cat VALUES (184, 2000032001, 160, 160, 'asp', 'ASP', 'Active Server Pages', 0, 0, 'Programming Language :: ASP', '160 :: 184');
INSERT INTO trove_cat VALUES (185, 2000032001, 160, 160, 'shell', 'Unix Shell', 'Unix Shell', 0, 0, 'Programming Language :: Unix Shell', '160 :: 185');
INSERT INTO trove_cat VALUES (186, 2000032001, 160, 160, 'visualbasic', 'Visual Basic', 'Visual Basic', 0, 0, 'Programming Language :: Visual Basic', '160 :: 186');
INSERT INTO trove_cat VALUES (276, 2001122001, 160, 160, 'rebol', 'Rebol', 'The Rebol programming language', 0, 0, 'Programming Language :: Rebol', '160 :: 276');
INSERT INTO trove_cat VALUES (278, 2002051501, 6, 6, 'endoflife', '7 - End of Life', 'The software project has come to an end and it is not expected to evolve in the future', 0, 0, 'Development Status :: 7 - End of Life', '6 :: 278');
INSERT INTO trove_cat VALUES (194, 2000111301, 13, 13, 'osi', 'Open Source Approved license', 'Open Source approved licenses. Use one of these only if Open Sourcing your software has been explicitely approved by your legal department.', 0, 0, 'License :: Open Source Approved license', '13 :: 194');
INSERT INTO trove_cat VALUES (196, 2000040701, 13, 13, 'other', 'Other/Proprietary License', 'Non OSI-Approved/Proprietary license.', 0, 0, 'License :: Other/Proprietary License', '13 :: 196');
INSERT INTO trove_cat VALUES (272, 2000120801, 132, 18, 'ui', 'User Interface', 'Everything dealing with Computer UI such as new user interface paradigm, Graphical Toolkit, Widgets library,...', 0, 0, 'Topic :: Information Technology :: User Interface', '18 :: 132 :: 272');
INSERT INTO trove_cat VALUES (198, 2000032001, 160, 160, 'java', 'Java', 'Java', 0, 0, 'Programming Language :: Java', '160 :: 198');
INSERT INTO trove_cat VALUES (199, 2000032101, 0, 0, 'os', 'Operating System', 'What operating system the program requires to run, if any.', 0, 0, 'Operating System', '199');
INSERT INTO trove_cat VALUES (200, 2000032101, 199, 199, 'posix', 'POSIX', 'POSIX plus standard Berkeley socket facilities. Don\'t list a more specific OS unless your program requires it.', 0, 0, 'Operating System :: POSIX', '199 :: 200');
INSERT INTO trove_cat VALUES (201, 2000032101, 200, 199, 'linux', 'Linux', 'Any version of Linux. Don\'t specify a subcategory unless the program requires a particular distribution.', 0, 0, 'Operating System :: POSIX :: Linux', '199 :: 200 :: 201');
INSERT INTO trove_cat VALUES (202, 2000111301, 200, 199, 'bsd', 'BSD', 'Any variant of BSD (FreeBSD, NetBSD, Open BSD, etc.)', 0, 0, 'Operating System :: POSIX :: BSD', '199 :: 200 :: 202');
INSERT INTO trove_cat VALUES (207, 2000032101, 200, 199, 'sun', 'SunOS/Solaris', 'Any Sun Microsystems OS.', 0, 0, 'Operating System :: POSIX :: SunOS/Solaris', '199 :: 200 :: 207');
INSERT INTO trove_cat VALUES (208, 2000032101, 200, 199, 'sco', 'SCO', 'SCO', 0, 0, 'Operating System :: POSIX :: SCO', '199 :: 200 :: 208');
INSERT INTO trove_cat VALUES (209, 2000032101, 200, 199, 'hpux', 'HP-UX', 'HP-UX', 0, 0, 'Operating System :: POSIX :: HP-UX', '199 :: 200 :: 209');
INSERT INTO trove_cat VALUES (210, 2000032101, 200, 199, 'aix', 'AIX', 'AIX', 0, 0, 'Operating System :: POSIX :: AIX', '199 :: 200 :: 210');
INSERT INTO trove_cat VALUES (211, 2000032101, 200, 199, 'irix', 'IRIX', 'IRIX', 0, 0, 'Operating System :: POSIX :: IRIX', '199 :: 200 :: 211');
INSERT INTO trove_cat VALUES (212, 2000032101, 200, 199, 'other', 'Other', 'Other specific POSIX OS, specified in description.', 0, 0, 'Operating System :: POSIX :: Other', '199 :: 200 :: 212');
INSERT INTO trove_cat VALUES (213, 2000032101, 160, 160, 'other', 'Other', 'Other programming language, specified in description.', 0, 0, 'Programming Language :: Other', '160 :: 213');
INSERT INTO trove_cat VALUES (214, 2000032101, 199, 199, 'microsoft', 'Microsoft', 'Microsoft operating systems.', 0, 0, 'Operating System :: Microsoft', '199 :: 214');
INSERT INTO trove_cat VALUES (215, 2000032101, 214, 199, 'msdos', 'MS-DOS', 'Microsoft Disk Operating System (DOS)', 0, 0, 'Operating System :: Microsoft :: MS-DOS', '199 :: 214 :: 215');
INSERT INTO trove_cat VALUES (216, 2000032101, 214, 199, 'windows', 'Windows', 'Windows software, not specific to any particular version of Windows.', 0, 0, 'Operating System :: Microsoft :: Windows', '199 :: 214 :: 216');
INSERT INTO trove_cat VALUES (217, 2000032101, 216, 199, 'win31', 'Windows 3.1 or Earlier', 'Windows 3.1 or Earlier', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows 3.1 or Earlier', '199 :: 214 :: 216 :: 217');
INSERT INTO trove_cat VALUES (218, 2000032101, 216, 199, 'win95', 'Windows 95/98/2000', 'Windows 95, Windows 98, and Windows 2000.', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows 95/98/2000', '199 :: 214 :: 216 :: 218');
INSERT INTO trove_cat VALUES (219, 2000041101, 216, 199, 'winnt', 'Windows NT/2000', 'Windows NT and Windows 2000.', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows NT/2000', '199 :: 214 :: 216 :: 219');
INSERT INTO trove_cat VALUES (220, 2000032101, 199, 199, 'os2', 'OS/2', 'OS/2', 0, 0, 'Operating System :: OS/2', '199 :: 220');
INSERT INTO trove_cat VALUES (221, 2000032101, 199, 199, 'macos', 'MacOS', 'MacOS', 0, 0, 'Operating System :: MacOS', '199 :: 221');
INSERT INTO trove_cat VALUES (222, 2000032101, 216, 199, 'wince', 'Windows CE', 'Windows CE', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows CE', '199 :: 214 :: 216 :: 222');
INSERT INTO trove_cat VALUES (223, 2000032101, 199, 199, 'palmos', 'PalmOS', 'PalmOS (for Palm Pilot)', 0, 0, 'Operating System :: PalmOS', '199 :: 223');
INSERT INTO trove_cat VALUES (224, 2000032101, 199, 199, 'beos', 'BeOS', 'BeOS', 0, 0, 'Operating System :: BeOS', '199 :: 224');
INSERT INTO trove_cat VALUES (225, 2000032101, 0, 0, 'environment', 'Environment', 'Run-time environment required for this program.', 0, 0, 'Environment', '225');
INSERT INTO trove_cat VALUES (226, 2000041101, 225, 225, 'console', 'Console (Text Based)', 'Console-based programs.', 0, 0, 'Environment :: Console (Text Based)', '225 :: 226');
INSERT INTO trove_cat VALUES (227, 2000032401, 226, 225, 'curses', 'Curses', 'Curses-based software.', 0, 0, 'Environment :: Console (Text Based) :: Curses', '225 :: 226 :: 227');
INSERT INTO trove_cat VALUES (228, 2000040701, 226, 225, 'newt', 'Newt', 'Newt', 0, 0, 'Environment :: Console (Text Based) :: Newt', '225 :: 226 :: 228');
INSERT INTO trove_cat VALUES (229, 2000040701, 225, 225, 'x11', 'X11 Applications', 'Programs that run in an X windowing environment.', 0, 0, 'Environment :: X11 Applications', '225 :: 229');
INSERT INTO trove_cat VALUES (230, 2000040701, 225, 225, 'win32', 'Win32 (MS Windows)', 'Programs designed to run in a graphical Microsoft Windows environment.', 0, 0, 'Environment :: Win32 (MS Windows)', '225 :: 230');
INSERT INTO trove_cat VALUES (231, 2000040701, 229, 225, 'gnome', 'Gnome', 'Programs designed to run in a Gnome environment.', 0, 0, 'Environment :: X11 Applications :: Gnome', '225 :: 229 :: 231');
INSERT INTO trove_cat VALUES (232, 2000040701, 229, 225, 'kde', 'KDE', 'Programs designed to run in a KDE environment.', 0, 0, 'Environment :: X11 Applications :: KDE', '225 :: 229 :: 232');
INSERT INTO trove_cat VALUES (233, 2000040701, 225, 225, 'other', 'Other Environment', 'Programs designed to run in an environment other than one listed.', 0, 0, 'Environment :: Other Environment', '225 :: 233');
INSERT INTO trove_cat VALUES (234, 2000040701, 18, 18, 'other', 'Other/Nonlisted Topic', 'Topic does not fit into any listed category.', 0, 0, 'Topic :: Other/Nonlisted Topic', '18 :: 234');
INSERT INTO trove_cat VALUES (235, 2000041001, 199, 199, 'independent', 'OS Independent', 'This software does not depend on any particular operating system.', 0, 0, 'Operating System :: OS Independent', '199 :: 235');
INSERT INTO trove_cat VALUES (236, 2000040701, 199, 199, 'other', 'Other OS', 'Program is designe for a nonlisted operating system.', 0, 0, 'Operating System :: Other OS', '199 :: 236');
INSERT INTO trove_cat VALUES (237, 2000041001, 225, 225, 'web', 'Web Environment', 'This software is designed for a web environment.', 0, 0, 'Environment :: Web Environment', '225 :: 237');
INSERT INTO trove_cat VALUES (238, 2000041101, 225, 225, 'daemon', 'No Input/Output (Daemon)', 'This program has no input or output, but is intended to run in the background as a daemon.', 0, 0, 'Environment :: No Input/Output (Daemon)', '225 :: 238');
INSERT INTO trove_cat VALUES (240, 2000041301, 200, 199, 'gnuhurd', 'GNU Hurd', 'GNU Hurd', 0, 0, 'Operating System :: POSIX :: GNU Hurd', '199 :: 200 :: 240');
INSERT INTO trove_cat VALUES (242, 2000042701, 160, 160, 'scheme', 'Scheme', 'Scheme programming language.', 0, 0, 'Programming Language :: Scheme', '160 :: 242');
INSERT INTO trove_cat VALUES (254, 2000071101, 160, 160, 'plsql', 'PL/SQL', 'PL/SQL Programming Language', 0, 0, 'Programming Language :: PL/SQL', '160 :: 254');
INSERT INTO trove_cat VALUES (255, 2000071101, 160, 160, 'progress', 'PROGRESS', 'PROGRESS Programming Language', 0, 0, 'Programming Language :: PROGRESS', '160 :: 255');
INSERT INTO trove_cat VALUES (258, 2000071101, 160, 160, 'objectpascal', 'Object Pascal', 'Object Pascal', 0, 0, 'Programming Language :: Object Pascal', '160 :: 258');
INSERT INTO trove_cat VALUES (261, 2000072501, 160, 160, 'xbasic', 'XBasic', 'XBasic programming language', 0, 0, 'Programming Language :: XBasic', '160 :: 261');
INSERT INTO trove_cat VALUES (262, 2000073101, 160, 160, 'coldfusion', 'Cold Fusion', 'Cold Fusion Language', 0, 0, 'Programming Language :: Cold Fusion', '160 :: 262');
INSERT INTO trove_cat VALUES (263, 2000080401, 160, 160, 'euphoria', 'Euphoria', 'Euphoria programming language - http://www.rapideuphoria.com/', 0, 0, 'Programming Language :: Euphoria', '160 :: 263');
INSERT INTO trove_cat VALUES (264, 2000080701, 160, 160, 'erlang', 'Erlang', 'Erlang - developed by Ericsson - http://www.erlang.org/', 0, 0, 'Programming Language :: Erlang', '160 :: 264');
INSERT INTO trove_cat VALUES (265, 2000080801, 160, 160, 'Delphi', 'Delphi', 'Borland/Inprise Delphi', 0, 0, 'Programming Language :: Delphi', '160 :: 265');
INSERT INTO trove_cat VALUES (267, 2000082001, 160, 160, 'zope', 'Zope', 'Zope Object Publishing', 0, 0, 'Programming Language :: Zope', '160 :: 267');
INSERT INTO trove_cat VALUES (269, 2001010901, 160, 160, 'ruby', 'Ruby', 'A pragmatic, purely OO, extremelly elegant programming language offering the best of Perl, Python, Smalltalk and Eiffel. Worth a try ! (See http://www.ruby-lang.org)', 0, 0, 'Programming Language :: Ruby', '160 :: 269');
INSERT INTO trove_cat VALUES (273, 2001011601, 160, 160, 'matlab', 'Matlab', 'The Matlab (Matrix Laboratory) programming language for scientific and engineering numeric computation', 0, 0, 'Programming Language :: Matlab', '160 :: 273');
INSERT INTO trove_cat VALUES (279, 2002081301, 154, 18, 'printdrivers', 'Drivers', 'Printer drivers', 0, 0, 'Topic :: Printing :: Drivers', '18 :: 154 :: 279');
INSERT INTO trove_cat VALUES (280, 2008012101, 160, 160, 'vbdotnet', 'VB.NET', 'The VB.NET programming language', 0, 0, 'Programming Language :: VB.NET', '160 :: 280');
INSERT INTO trove_cat VALUES (281, 2008012101, 160, 160, 'csharp', 'C#', 'The C# programming language', 0, 0, 'Programming Language :: C#', '160 :: 281');
INSERT INTO trove_cat VALUES (282, 2008012101, 160, 160, 'javascript', 'JavaScript', 'The JavaScript programming language', 0, 0, 'Programming Language :: JavaScript', '160 :: 282');
    
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
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (11, 100, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=$group_id', 1, 0, 'system', 110);
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
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (37, 1, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=1', 1, 0, 'system', 110);
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
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Open Discussion','1' ,'General Discussion');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Help','1' ,'Get Help');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Developers','0' ,'Project Developer Discussion');

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

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, rank) 
SELECT user_id, 'u', 1, 1, 'mysurveys', 4
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



--
-- Tables for id sharing
--
SELECT @last_insert_artifact := IFNULL(MAX(artifact_id), 0) + 1 FROM artifact;
SET @s = CONCAT("ALTER TABLE tracker_idsharing_artifact AUTO_INCREMENT =  ", @last_insert_artifact);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT @last_insert_tracker := IFNULL(MAX(group_artifact_id), 0) + 1 FROM artifact_group_list;
SET @s = CONCAT("ALTER TABLE tracker_idsharing_tracker AUTO_INCREMENT = ", @last_insert_tracker);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
#
# EOF
#
