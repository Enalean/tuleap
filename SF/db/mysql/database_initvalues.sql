#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    Insert default values in all the CodeX tables.



#
# Default Data for 'user'
#
#
# Insert user 'none' with reserved user_id 100
# Also create the 'admin' user with default password 'siteadmin' and make
# it a member of the group_id 1 later on so that he as a super user status
# for the entire site

INSERT INTO user VALUES (100, 'None', 'noreply@_DOMAIN_NAME_', \
'*********34343', '0', NULL, 'S', '0', '0', '0', 0, '0', 940000000, NULL, 1, \
0, 0, NULL, NULL, 0, '', 'GMT', '', 0, '');

INSERT INTO user VALUES (101, 'admin', '', \
'6f3cac6213ffceee27cc85414f458caa', 'Site Administrator', NULL, 'A', \
'/bin/bash', '$1$Sn;W@$PXu/wJEYCCN2.BmF2uSfT/', 'A', 1,'shell1', \
940000000, NULL, 0, 0, 0, NULL, NULL, 0, '', 'GMT', \
'AD3682DB98997A758E5D533411003C5C:2E17AE860AC9D678CD6B9C16DBBA6006', 0, '');

#
# Default Data for 'groups'
#

INSERT INTO groups SET \
  group_id = '1', \
  group_name = 'CodeX Administration Project', \
  is_public = '1', \
  status = 'A', \
  unix_group_name = 'codex', \
  unix_box = 'shell1', \
  http_domain = 'codex._DOMAIN_NAME_', \
  short_description = 'CodeX Administration Project', \
  cvs_box = 'cvs1', \
  svn_box = 'svn1', \
  license = 'xrx', \
  register_purpose = '', \
  required_software = '', \
  patents_ips = '', \
  other_comments = '', \
  license_other = '', \
  register_time = 940000000, \
  rand_hash = '', \
  new_bug_address = 'codex-admin@_DOMAIN_NAME_', \
  new_patch_address = 'codex-admin@_DOMAIN_NAME_', \
  new_support_address = 'codex-admin@_DOMAIN_NAME_', \
  type = '1', \
  send_all_bugs = '1', \
  send_all_patches = '1', \
  send_all_support = '1', \
  bug_preamble = '', \
  support_preamble = '', \
  patch_preamble = '', \
  pm_preamble = '', \
  xrx_export_ettm = '0', \
  project_type = '0', \
  bug_allow_anon = '1', \
  cvs_tracker = '1', \
  cvs_events_mailing_list = '', \
  cvs_events_mailing_header = '', \
  cvs_preamble = '', \
  svn_tracker = '1', \
  svn_events_mailing_list = '', \
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
  license = 'xrx', \
  register_purpose = 'Site News Private Project\r\n\r\n', \
  required_software = '', \
  patents_ips = '', \
  other_comments = '', \
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
  project_type = '0', \
  bug_allow_anon = '1', \
  cvs_tracker = '0', \
  cvs_events_mailing_list = '', \
  cvs_events_mailing_header = '', \
  cvs_preamble = '', \
  svn_tracker = '0', \
  svn_events_mailing_list = '', \
  svn_events_mailing_header = '', \
  svn_preamble = '' ;


INSERT INTO groups SET \
  group_id = '100', \
  group_name = 'none', \
  is_public = '0', \
  status = 's', \
  unix_group_name = 'none', \
  unix_box = 'shell1', \
  http_domain = '', \
  short_description = 'Reserved project with group_id = 100 (None)', \
  cvs_box = '', \
  svn_box = '', \
  license = '', \
  register_purpose = '', \
  required_software = '', \
  patents_ips = '', \
  other_comments = '', \
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
  project_type = '0', \
  bug_allow_anon = '1', \
  cvs_tracker = '0', \
  cvs_events_mailing_list = '', \
  cvs_events_mailing_header = '', \
  cvs_preamble = '', \
  svn_tracker = '0', \
  svn_events_mailing_list = '', \
  svn_events_mailing_header = '', \
  svn_preamble = '' ;

#
# Default Data for 'user_group'
#
# Make the 'admin' user part of the default Admin Project so that he
# becomes a super user

INSERT INTO user_group VALUES (1, 101, 1, 'A', 2, 2, 2, 2, 2, 1, 2);

#
# Default bug (None)
#
INSERT INTO bug VALUES (100, 100, 3, 1, 100, 100, 100, 1058260700, 'None', '', 0, 100, 100, 100, 100, 100, 100, 100, 100, '0.00', '', '', '', 100, '', 100, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 0, 0, 0, 0, 0);

#
#  Default data for group_type
#
INSERT INTO group_type VALUES ('1','Project');
#INSERT INTO group_type VALUES ('2','Foundry');

#
# Default data for Support Request Manager
#

INSERT INTO support (support_id,group_id) VALUES ('100','100');

INSERT INTO support_messages (support_message_id,support_id) VALUES ('100','100');

INSERT INTO support_canned_responses (support_canned_id,group_id) VALUES ('100','100');

insert into support_status values('1','Open');
insert into support_status values('2','Closed');
insert into support_status values('3','Deleted');

insert into support_category VALUES ('100','100','None');


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

INSERT INTO frs_status VALUES ('1','Active');
INSERT INTO frs_status VALUES ('3','Hidden');

INSERT INTO frs_processor VALUES ('1000','i386');
INSERT INTO frs_processor VALUES ('2000','PPC');
INSERT INTO frs_processor VALUES ('3000','MIPS');
INSERT INTO frs_processor VALUES ('4000','Sparc');
INSERT INTO frs_processor VALUES ('5000','UltraSparc');
INSERT INTO frs_processor VALUES ('6000','IA64');
INSERT INTO frs_processor VALUES ('7000','Alpha');
INSERT INTO frs_processor VALUES ('8000','Any');
INSERT INTO frs_processor VALUES ('9999','Other');

#
# Default data for Document Manager
#

INSERT INTO doc_states VALUES (1, 'active');
INSERT INTO doc_states VALUES (2, 'deleted');
INSERT INTO doc_states VALUES (3, 'pending');
INSERT INTO doc_states VALUES (4, 'hidden');
INSERT INTO doc_states VALUES (5, 'private');

#
# Default data for Patch Manager
#

INSERT INTO patch_category VALUES (100,100,'None');

INSERT INTO patch_status VALUES (1, 'Open');
INSERT INTO patch_status VALUES (2, 'Closed');
INSERT INTO patch_status VALUES (3, 'Deleted');
INSERT INTO patch_status VALUES (4, 'Postponed');
INSERT INTO patch_status VALUES (100, 'None');

#
# Default data for Task Manager
#
    
INSERT INTO project_group_list VALUES (100,100,'none',0,NULL,0);

INSERT INTO project_status VALUES (1, 'Open');
INSERT INTO project_status VALUES (2, 'Closed');
INSERT INTO project_status VALUES (100, 'None');
INSERT INTO project_status VALUES (3, 'Deleted');
INSERT INTO project_status VALUES (4, 'Suspended');

INSERT INTO project_task VALUES (100, 100, 'None', '', 0, 0, '0.00', 0, 0, 0, 0);


#
# Default Data for Survey Manager
#

INSERT INTO survey_question_types (id, type) VALUES (1,'Radio Buttons 1-5');
INSERT INTO survey_question_types (id, type) VALUES (2,'Text Area');
INSERT INTO survey_question_types (id, type) VALUES (3,'Radio Buttons Yes/No');
INSERT INTO survey_question_types (id, type) VALUES (4,'Comment Only');
INSERT INTO survey_question_types (id, type) VALUES (5,'Text Field');
INSERT INTO survey_question_types (id, type) VALUES (100,'None');

#
# Developer survey attached to project #1, default status is inactive
#
INSERT INTO surveys (survey_id, group_id, survey_title, is_active) VALUES (1,1,'Developer Survey',0);
 

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
INSERT INTO trove_cat VALUES (14, 2000111301, 13, 13, 'xrx', 'Xerox Code eXchange Policy', 'The default Policy ruling the code sharing attitude in Xerox.', 0, 0, 'License :: Xerox Code eXchange Policy', '13 :: 14');
INSERT INTO trove_cat VALUES (274, 2001061501, 154, 18, 'printservices', 'Print Services', 'XAC/DDA/Print Services Projects', 0, 0, 'Topic :: Printing :: Print Services', '18 :: 154 :: 274');
INSERT INTO trove_cat VALUES (275, 2001062601, 160, 160, 'JSP', 'JSP', 'Java Server Pages: Sun\'s Java language embedded in HTML pages', 0, 0, 'Programming Language :: JSP', '160 :: 275');
INSERT INTO trove_cat VALUES (18, 2000031601, 0, 0, 'topic', 'Topic', 'Topic categorization.', 0, 0, 'Topic', '18');
INSERT INTO trove_cat VALUES (20, 2000111301, 18, 18, 'coomunications', 'Internet/Intranet Connectivity', 'Protocols, Languages, Applications intended to facilitate communication between people nad/or computers', 0, 0, 'Topic :: Internet/Intranet Connectivity', '18 :: 20');
INSERT INTO trove_cat VALUES (22, 2000111301, 18, 18, 'docmgt', 'Document Management', 'All document related software (e.g. Doct services, repository, design/creation, encoding like Glyph or Barcode, formatting and document output like printing)', 0, 0, 'Topic :: Document Management', '18 :: 22');
INSERT INTO trove_cat VALUES (37, 2000111301, 20, 18, 'wireless', 'Wireless Communication', 'Tools supporting wireless communication (radio, IR,...)', 0, 0, 'Topic :: Internet/Intranet Connectivity :: Wireless Communication', '18 :: 20 :: 37');
INSERT INTO trove_cat VALUES (43, 2000111301, 18, 18, 'imagemgt', 'Image Management', 'Software to help capture, manipulate, transform, render images (e.g. image processing, color management, printing/marking, image capture, image compression technics, etc.)', 0, 0, 'Topic :: Image Management', '18 :: 43');
INSERT INTO trove_cat VALUES (45, 2000111301, 18, 18, 'development', 'Software Development', 'Software used to aid software development ( e.g. language interpreters, compilers, debuggers, project management tools, build tools, Devt Environment, Devt Framework,etc.)', 0, 0, 'Topic :: Software Development', '18 :: 45');
INSERT INTO trove_cat VALUES (97, 2000111301, 18, 18, 'scientific', 'Scientific/Engineering', 'Scientific or Engineering applications, to include research on non computer related sciences. (e.g. Physics and Mathematics in general, Xerography, Data Visualization Tools, etc.)', 0, 0, 'Topic :: Scientific/Engineering', '18 :: 97');
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
INSERT INTO trove_cat VALUES (194, 2000111301, 13, 13, 'osi', 'Open Source Approved license', 'Open Source approved licenses. Use one of these only if Open Sourcing your Xerox software has been explicitely approved by the Xerox COMIP.', 0, 0, 'License :: Open Source Approved license', '13 :: 194');
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

    

#
#  Default values for the Bug Tracking System
#

# ==============================
# Bug field table
# ==============================
#
# Historical fields first
# Rk: bug_id, group_id, date, close_date, submitted_by are special fields
# because they are not entered by the user. Summary, details and comment type
# are special fields as well because they require special processing.
# They are in this table because we want to harmonize label and description
# management throughout the bug tracking system
#
INSERT INTO bug_field \
  VALUES (90,'bug_id','TF','6/10','Bug ID','Unique bug identifier','S',1,0,0,1,0,NULL);
INSERT INTO bug_field \
  VALUES (91,'group_id','TF','','Group ID','Unique project identifier','S',1,0,0,1,0,NULL);
INSERT INTO bug_field \
  VALUES (92,'submitted_by','SB','','Submitted by','User who originally submitted the bug','S',1,1,0,1,0,NULL);
INSERT INTO bug_field \
  VALUES (93,'date','DF','10/15','Submitted on','Date and time for the initial bug submission','S',1,0,0,1,0,'bug_submitters');
INSERT INTO bug_field \
  VALUES (94,'close_date','DF','10/15','Closed on','Date and time when the bug status was changed to ''Closed''','S',1,1,0,1,0,NULL);
INSERT INTO bug_field \
  VALUES (101,'status_id','SB','','Status','Bug Status','P',1,0,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (102,'severity','SB','','Severity','Impact of the bug on the system (Critical, Major,...)','S',1,0,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (103,'category_id','SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (104,'assigned_to','SB','','Assigned to','Who is in charge of solving the bug','S',1,1,1,0,0,'bug_technicians');
INSERT INTO bug_field \
  VALUES (105,'summary','TF','60/120','Summary','One line description of the bug','S',1,0,1,1,0,NULL);
INSERT INTO bug_field \
  VALUES (106,'details','TA','60/7','Original Submission','A full description of the bug','S',1,1,1,1,0,NULL);
INSERT INTO bug_field \
  VALUES (107,'bug_group_id','SB','','Bug Group','Characterizes the nature of the bug (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (108,'resolution_id','SB','','Resolution','How you have decided to fix the bug (Fixed, Work for me, Duplicate,..)','S',1,1,1,0,0,NULL);
#
# -----------------------------
#
# New Extension fields second
#
INSERT INTO bug_field \
  VALUES (200,'category_version_id','SB','','Component Version','The version of the System Component (aka Bug Category) impacted by the bug','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (201,'platform_version_id','SB','','Platform Version','The name and version of the platform your software was running on when the bug occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (202,'reproducibility_id','SB','','Reproducibility','How easy is it to reproduce the bug','S',0,0,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (203,'size_id','SB','','Size (loc)','The size of the code you need to develop or rework in order to fix the bug','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (204,'fix_release_id','SB','','Fixed Release','The release in which the bug was actually fixed','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (205,'comment_type_id','SB','','Comment Type','Specify the nature of the  follow up comment attached to this bug (Workaround, Test Case, Impacted Files,...)','P',1,1,0,1,0,NULL);
INSERT INTO bug_field \
  VALUES (206,'hours','TF','5/5','Effort','Number of hours of work needed to fix the bug (including testing)','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (207,'plan_release_id','SB','','Planned Release','The release in which you initially planned the bug to be fixed','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (208,'component_version','TF','10/40','Component Version','Version of the system component (or work product) impacted by the bug. Same as the other Component Version field <u>except</u> this one is free text.','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (209,'fix_release','TF','10/40','Fixed Release','The release in which the bug was actually fixed. Same as the other Fixed Release field <u>except</u> this one is free text.','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (210,'plan_release','TF','10/40','Planned Release','The release in which you initially planned the bug to be fixed. Same as the other Planned Release field <u>except</u> this one is free text.','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (211,'priority','SB','','Priority','How quickly the bug must be fixed (Immediate, Normal, Low, Later,...)','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (212,'keywords','TF','60/120','Keywords','A list of comma separated keywords associated with a bug','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (213,'release_id','SB','','Release','The release (global version number) impacted by the bug','P',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (214,'release','TF','10/40','Release','The release (global version number) impacted by the bug. Same as the other Release field <u>except</u> this one is free text.','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (215,'originator_name','TF','20/40','Originator Name','The name of the person who reported the bug (if different from the submitter field)','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (216,'originator_email','TF','20/40','Originator Email','Email address of the person who reported the bug. Automatically included in the bug email notification process.','S',0,1,1,0,0,NULL);
INSERT INTO bug_field \
  VALUES (217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who reported the bug','S',0,1,1,0,0,NULL);

#
# Customizable text fields 
#
INSERT INTO bug_field \
  VALUES (300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1,NULL);
#
# Customizable text areas 
#
INSERT INTO bug_field \
  VALUES (400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1,NULL);
#
# Customizable select boxes
#
INSERT INTO bug_field \
  VALUES (500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1,NULL);
#
# Customizable date fields
#
INSERT INTO bug_field \
  VALUES (600,'custom_df1','DF','10/10','Custom Date Field #1','Customizable Date Field','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (601,'custom_df2','DF','10/10','Custom Date Field #2','Customizable Date Field','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (602,'custom_df3','DF','10/10','Custom Date Field #3','Customizable Date Field','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (603,'custom_df4','DF','10/10','Custom Date Field #4','Customizable Date Field','P',0,1,1,0,1,NULL);
INSERT INTO bug_field \
  VALUES (604,'custom_df5','DF','10/10','Custom Date Field #5','Customizable Date Field','P',0,1,1,0,1,NULL);

# ==============================
# Bug field value table
# ==============================
#
# Rk: to avoid breaking the existing PHP scripts all the value_id
# of historical fields are preserved.
# Rk2: For system scope fields ('S') it doesn't make much difference whether
# a field is Permanent or Active since a project cannot act on this field
# anyway. But mark as permanent all the values we consider as rock solid
# regardless of whether they are project or system scope.
#
# Status (bug_field_id = 101)
#
INSERT INTO bug_field_value VALUES (101,101,100,1,'Open','The bug has been submitted',20,'P');
INSERT INTO bug_field_value VALUES (102,101,100,3,'Closed','The bug is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO bug_field_value VALUES (104,101,100,4,'Analyzed','The cause of the bug has been identified and documented',30,'H');
INSERT INTO bug_field_value VALUES (105,101,100,5,'Accepted','The bug will be worked on. If it won''t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO bug_field_value VALUES (106,101,100,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO bug_field_value VALUES (107,101,100,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO bug_field_value VALUES (108,101,100,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO bug_field_value VALUES (109,101,100,9,'Approved','The bug fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO bug_field_value VALUES (110,101,100,10,'Declined','The bug was not accepted. Alternatively, you can also Set the status to "Closed" and use the Resolution field to explain why it was declined',150,'H');

# Severity (bug_field_id = 102)
#
# 
INSERT INTO bug_field_value VALUES (131,102,100,1,'1 - Ordinary','',10,'A');
INSERT INTO bug_field_value VALUES (132,102,100,2,'2','',20,'A');
INSERT INTO bug_field_value VALUES (133,102,100,3,'3','',30,'A');
INSERT INTO bug_field_value VALUES (134,102,100,4,'4','',40,'A');
INSERT INTO bug_field_value VALUES (135,102,100,5,'5 - Major','',50,'A');
INSERT INTO bug_field_value VALUES (136,102,100,6,'6','',60,'A');
INSERT INTO bug_field_value VALUES (137,102,100,7,'7','',70,'A');
INSERT INTO bug_field_value VALUES (138,102,100,8,'8','',80,'A');
INSERT INTO bug_field_value VALUES (139,102,100,9,'9 - Critical','',90,'A');

# Category (bug_field_id = 103)
#
INSERT INTO bug_field_value VALUES (150,103,100,100,'None','',10,'P');

# Group (bug_field_id = 107)
#
INSERT INTO bug_field_value VALUES (160,107,100,100,'None','',10,'P');

# Resolution (bug_field_id = 108)
#
INSERT INTO bug_field_value VALUES (170,108,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (171,108,100,1,'Fixed','The bug was resolved',20,'A');
INSERT INTO bug_field_value VALUES (172,108,100,2,'Invalid','The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)',30,'A');
INSERT INTO bug_field_value VALUES (173,108,100,3,'Wont Fix','The bug won''t be fixed (probably because it is very minor)',40,'A');
INSERT INTO bug_field_value VALUES (174,108,100,4,'Later','The bug will be fixed later (no date given)',50,'A');
INSERT INTO bug_field_value VALUES (175,108,100,5,'Remind','The bug will be fixed later but keep in the remind state for easy identification',60,'A');
INSERT INTO bug_field_value VALUES (176,108,100,6,'Works for me','The project team was unable to reproduce the bug',70,'A');
INSERT INTO bug_field_value VALUES (177,108,100,7,'Duplicate','This bug is already covered by another bug description (see related bugs list)',80,'A');

# Component Version (bug_field_id = 200)
#
INSERT INTO bug_field_value VALUES (200,200,100,100,'None','',10,'P');

# Platform Version (bug_field_id = 201)
#
INSERT INTO bug_field_value VALUES (210,201,100,100,'None','',10,'P');

# Reproducibility (bug_field_id = 202)
#
INSERT INTO bug_field_value VALUES (220,202,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (221,202,100,110,'Every Time','',20,'P');
INSERT INTO bug_field_value VALUES (222,202,100,120,'Intermittent','',30,'P');
INSERT INTO bug_field_value VALUES (223,202,100,130,'Once','',40,'P');

# Size (bug_field_id = 203)
#
INSERT INTO bug_field_value VALUES (240,203,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (241,203,100,110,'Low <30','',20,'A');
INSERT INTO bug_field_value VALUES (242,203,100,120,'Medium 30 - 200','',30,'A');
INSERT INTO bug_field_value VALUES (243,203,100,130,'High >200','',40,'A');

# Fixed Release (bug_field_id = 204)
#
INSERT INTO bug_field_value VALUES (250,204,100,100,'None','',10,'P');

# Comment Type (bug_field_id = 205)
#
INSERT INTO bug_field_value VALUES (260,205,100,100,'None','',10,'P');

# Planned Release (bug_field_id = 207)
#
INSERT INTO bug_field_value VALUES (270,207,100,100,'None','',10,'P');

# Priority (bug_field_id = 211)
#
INSERT INTO bug_field_value VALUES (280,211,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (281,211,100,120,'Later','',20,'A');
INSERT INTO bug_field_value VALUES (282,211,100,130,'Later+','',30,'H');
INSERT INTO bug_field_value VALUES (283,211,100,140,'Later++','',40,'H');
INSERT INTO bug_field_value VALUES (284,211,100,150,'Low','',50,'A');
INSERT INTO bug_field_value VALUES (285,211,100,160,'Low+','',60,'H');
INSERT INTO bug_field_value VALUES (286,211,100,170,'Low++','',70,'H');
INSERT INTO bug_field_value VALUES (287,211,100,180,'Normal','',80,'A');
INSERT INTO bug_field_value VALUES (288,211,100,190,'Normal+','',90,'H');
INSERT INTO bug_field_value VALUES (289,211,100,200,'Normal++','',100,'H');
INSERT INTO bug_field_value VALUES (290,211,100,210,'High','',110,'A');
INSERT INTO bug_field_value VALUES (291,211,100,220,'High+','',120,'H');
INSERT INTO bug_field_value VALUES (292,211,100,230,'High++','',130,'H');
INSERT INTO bug_field_value VALUES (293,211,100,240,'Immediate','',140,'A');
INSERT INTO bug_field_value VALUES (294,211,100,250,'Immediate+','',150,'H');
INSERT INTO bug_field_value VALUES (295,211,100,260,'Immediate++','',160,'H');

# Release (bug_field_id = 213)
#
INSERT INTO bug_field_value VALUES (300,213,100,100,'None','',10,'P');

# Customizable select boxes (bug_field_id = 500-5..)
#
INSERT INTO bug_field_value VALUES (400,500,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (401,501,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (402,502,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (403,503,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (404,504,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (405,505,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (406,506,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (407,507,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (408,508,100,100,'None','',10,'P');
INSERT INTO bug_field_value VALUES (409,509,100,100,'None','',10,'P');


# ==============================
# Bug field usage table
# ==============================
# Insert field usage information for group 100 (None). This will be
# the default pattern for all projects as long as they do not define
# their own settings
#
# Include all required fields as well in there for convenience in
# field processing but their settings cannot be changed anyway
#
# Remark: Any new field added in the bug_field table must have a
# corresponding row here to define its default value (group_id 100)

# Bug ID (bug_field_id = 90)
#
INSERT INTO bug_field_usage VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,NULL);

# Group ID (bug_field_id = 91)
#
INSERT INTO bug_field_usage VALUES (91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,NULL);

# Submitted by (bug_field_id = 92)
#
INSERT INTO bug_field_usage VALUES (92,100,1,0,0,20,NULL,NULL,NULL,NULL,NULL,NULL);

# Submitted on (bug_field_id = 93)
#
INSERT INTO bug_field_usage VALUES (93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,NULL);

# Close Date (bug_field_id = 94)
#
INSERT INTO bug_field_usage VALUES (94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,NULL);

# Status (bug_field_id = 101)
#
INSERT INTO bug_field_usage VALUES (101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,NULL);

# Severity (bug_field_id = 102)
#
INSERT INTO bug_field_usage VALUES (102,100,1,0,1,200,NULL,NULL,NULL,NULL,NULL,NULL);

# Category (bug_field_id = 103)
#
INSERT INTO bug_field_usage VALUES (103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,NULL);

# Assigned_to (bug_field_id = 104)
#
INSERT INTO bug_field_usage VALUES (104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,NULL);

# Summary (bug_field_id = 105)
#
INSERT INTO bug_field_usage VALUES (105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,NULL);

# Details (bug_field_id = 106)
#
INSERT INTO bug_field_usage VALUES (106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,NULL);

# Group - bug group (bug_field_id = 107)
#
INSERT INTO bug_field_usage VALUES (107,100,1,1,1,300,NULL,NULL,NULL,NULL,NULL,NULL);

# Resolution (bug_field_id = 108)
#
INSERT INTO bug_field_usage VALUES (108,100,1,0,0,400,NULL,NULL,NULL,NULL,NULL,NULL);

# category version id (bug_field_id = 200)
#
INSERT INTO bug_field_usage VALUES (200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,NULL);

# platform version (bug_field_id = 201)
#
INSERT INTO bug_field_usage VALUES (201,100,0,0,0,1100,NULL,NULL,NULL,NULL,NULL,NULL);

# reproducibility (bug_field_id = 202)
#
INSERT INTO bug_field_usage VALUES (202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,NULL);

# size (bug_field_id = 203)
#
INSERT INTO bug_field_usage VALUES (203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,NULL);

# fixed release (bug_field_id = 204)
#
INSERT INTO bug_field_usage VALUES (204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,NULL);

# comment type (bug_field_id = 205)
#
INSERT INTO bug_field_usage VALUES (205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,NULL);

# hours (bug_field_id = 206)
#
INSERT INTO bug_field_usage VALUES (206,100,0,0,0,1700,NULL,NULL,NULL,NULL,NULL,NULL);

# planned release (bug_field_id = 207)
#
INSERT INTO bug_field_usage VALUES (207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,NULL);

# component version string (bug_field_id = 208)
#
INSERT INTO bug_field_usage VALUES (208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,NULL);

# fixed release string (bug_field_id = 209)
#
INSERT INTO bug_field_usage VALUES (209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,NULL);

# planned release string (bug_field_id = 210)
#
INSERT INTO bug_field_usage VALUES (210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,NULL);

# priority (bug_field_id = 211)
#
INSERT INTO bug_field_usage VALUES (211,100,0,0,0,250,NULL,NULL,NULL,NULL,NULL,NULL);

# Keywords (bug_field_id = 212)
#
INSERT INTO bug_field_usage VALUES (212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,NULL);

# release id (bug_field_id = 213)
#
INSERT INTO bug_field_usage VALUES (213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,NULL);

# release (bug_field_id = 214)
#
INSERT INTO bug_field_usage VALUES (214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,NULL);

# originator name (bug_field_id = 215)
#
INSERT INTO bug_field_usage VALUES (215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,NULL);

# originator email (bug_field_id = 216)
#
INSERT INTO bug_field_usage VALUES (216,100,0,0,0,560,NULL,NULL,NULL,NULL,NULL,NULL);

# originator phone (bug_field_id = 217)
#
INSERT INTO bug_field_usage VALUES (217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,NULL);

# customizable text fields (bug_field_id= 300-...)
#
INSERT INTO bug_field_usage VALUES (300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,NULL);

# customizable text areas( (bug_field_id= 400-...)
#
INSERT INTO bug_field_usage VALUES (400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,NULL);

# customizable select boxes (bug_field_id= 500-...)
#
INSERT INTO bug_field_usage VALUES (500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,NULL);

# customizable date fields (bug_field_id = 600-...)
#
INSERT INTO bug_field_usage VALUES (600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO bug_field_usage VALUES (604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,NULL);

# ==============================
# Bug_report table
# ==============================
# Create the site default bug search report which correspond
# to what the initial SourceForge BTS was
#

INSERT INTO bug_report VALUES \
(100,100,100,'Default','The system default bug report','S');

# ==============================
# Bug_report_field table
# ==============================
# Create the site default bug search report which correspond
# to what the initial SourceForge BTS was

INSERT INTO bug_report_field VALUES (100,'category_id',1,0,10,NULL,NULL);
INSERT INTO bug_report_field VALUES (100,'bug_group_id',1,0,20,NULL,NULL);
INSERT INTO bug_report_field VALUES (100,'assigned_to',1,1,30,40,NULL);
INSERT INTO bug_report_field VALUES (100,'status_id',1,0,40,NULL,NULL);
INSERT INTO bug_report_field VALUES (100,'bug_id',0,1,NULL,10,NULL);
INSERT INTO bug_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO bug_report_field VALUES (100,'date',0,1,NULL,30,NULL);
INSERT INTO bug_report_field VALUES (100,'submitted_by',0,1,NULL,50,NULL);

# ==============================
# Bug_notification_role table
# ==============================
# Create the list of roles a user can play wrt to a bug
#
INSERT INTO bug_notification_role VALUES (1,'SUBMITTER','Submitter', 'The person who submitted the bug',10);
INSERT INTO bug_notification_role VALUES (2,'ASSIGNEE','Assignee','The person to whom the bug was assigned',20);
INSERT INTO bug_notification_role VALUES (3,'CC','CC','The person who is in the CC list',30);
INSERT INTO bug_notification_role VALUES (4,'COMMENTER','Commenter','A person who once posted a follow-up comment',40);

# ==============================
# Bug_notification_event table
# ==============================
# Create the list of events that can occur in a bug update
#
INSERT INTO bug_notification_event VALUES (1,'ROLE_CHANGE','Role has changed','I\'m added to or removed from this role',10);
INSERT INTO bug_notification_event VALUES (2,'NEW_COMMENT','New comment','A new followup comment is added',20);
INSERT INTO bug_notification_event VALUES (3,'NEW_FILE','New attachment','A new file attachment is added',30);
INSERT INTO bug_notification_event VALUES (4,'CC_CHANGE','CC Change','A new CC address is added/removed',40);
INSERT INTO bug_notification_event VALUES (5,'CLOSED','Bug closed','The bug is closed',50);
INSERT INTO bug_notification_event VALUES (6,'PSS_CHANGE','PSS change','Priority,Status,Severity changes',60);
INSERT INTO bug_notification_event VALUES (7,'ANY_OTHER_CHANGE','Any other Changes','Any changes not mentioned above',70);
INSERT INTO bug_notification_event VALUES (8,'I_MADE_IT','I did it','I am the author of the change',80);
INSERT INTO bug_notification_event VALUES (9,'NEW_BUG','New Bug','A new bug has been submitted',90);

# ==============================
# project_notification_role table
# ==============================
# Create the list of roles a user can play wrt to a task
#
INSERT INTO project_notification_role VALUES (1,'SUBMITTER','Submitter', 'The person who submitted the task',10);
INSERT INTO project_notification_role VALUES (2,'ASSIGNEE','Assignee','The person to whom the task was assigned',20);
INSERT INTO project_notification_role VALUES (3,'CC','CC','The person who is in the CC list',30);
INSERT INTO project_notification_role VALUES (4,'COMMENTER','Commenter','A person who once posted a follow-up comment',40);

# ==============================
# project_notification_event table
# ==============================
# Create the list of events that can occur in a project update
#
INSERT INTO project_notification_event VALUES (1,'ROLE_CHANGE','Role has changed','I\'m added to or removed from this role',10);
INSERT INTO project_notification_event VALUES (2,'NEW_COMMENT','New comment','A new followup comment is added',20);
INSERT INTO project_notification_event VALUES (3,'NEW_FILE','New attachment','A new file attachment is added',30);
INSERT INTO project_notification_event VALUES (4,'CC_CHANGE','CC Change','A new CC address is added/removed',40);
INSERT INTO project_notification_event VALUES (5,'CLOSED','Task closed','The task is closed',50);
INSERT INTO project_notification_event VALUES (6,'PSS_CHANGE','PSS change','Priority,Status changes',60);
INSERT INTO project_notification_event VALUES (7,'ANY_OTHER_CHANGE','Any other Changes','Any changes not mentioned above',70);
INSERT INTO project_notification_event VALUES (8,'I_MADE_IT','I did it','I am the author of the change',80);
INSERT INTO project_notification_event VALUES (9,'NEW_TASK','New Task','A new task has been submitted',90);

# ==============================
# Project field table
# ==============================
#
INSERT INTO project_field \
  VALUES (90,'project_task_id','TF','6/10','Project Task ID','Unique project task identifier','S',1,0,0,1,0);
INSERT INTO project_field \
  VALUES (91,'group_id','TF','','Group ID','Unique project identifier','S',1,0,0,1,0);
INSERT INTO project_field \
  VALUES (92,'created_by','SB','','Created by','User who originally created the task','S',1,0,0,1,0);
INSERT INTO project_field \
  VALUES (94,'start_date','DF','10/15','Start date','Date and time when the task starts','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (95,'end_date','DF','10/15','End date','Date and time when the task is finish','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (96,'group_project_id','SB','','Subproject','Subproject','P',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (97,'percent_complete','SB','','Percent complete','The percent completion','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (98,'hours','TF','5/10','Effort','The estimation to do the task','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (99,'priority','SB','','Priority','Level of priority for this task','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (100,'status_id','SB','','Status','Task status','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (102,'summary','TF','60/120','Summary','One line description of the task','S',1,0,1,1,0);
INSERT INTO project_field \
  VALUES (103,'details','TA','60/7','Original Submission','A full description of the task','S',1,1,1,1,0);

# ==============================
# Project field value table
# ==============================
#

#
# Status (percent_complete = 97)
#
INSERT INTO project_field_value VALUES (101,97,100,1000,'Not started','The task is not started',1,'P');
INSERT INTO project_field_value VALUES (102,97,100,1005,'5%','',5,'P');
INSERT INTO project_field_value VALUES (103,97,100,1015,'15%','',15,'P');
INSERT INTO project_field_value VALUES (104,97,100,1020,'20%','',20,'P');
INSERT INTO project_field_value VALUES (105,97,100,1025,'25%','',25,'P');
INSERT INTO project_field_value VALUES (106,97,100,1030,'30%','',30,'P');
INSERT INTO project_field_value VALUES (107,97,100,1035,'35%','',35,'P');
INSERT INTO project_field_value VALUES (108,97,100,1040,'40%','',40,'P');
INSERT INTO project_field_value VALUES (109,97,100,1045,'45%','',45,'P');
INSERT INTO project_field_value VALUES (110,97,100,1050,'50%','',50,'P');
INSERT INTO project_field_value VALUES (111,97,100,1055,'55%','',55,'P');
INSERT INTO project_field_value VALUES (112,97,100,1060,'60%','',60,'P');
INSERT INTO project_field_value VALUES (113,97,100,1065,'65%','',65,'P');
INSERT INTO project_field_value VALUES (114,97,100,1070,'70%','',70,'P');
INSERT INTO project_field_value VALUES (115,97,100,1075,'75%','',75,'P');
INSERT INTO project_field_value VALUES (116,97,100,1080,'80%','',80,'P');
INSERT INTO project_field_value VALUES (117,97,100,1085,'85%','',85,'P');
INSERT INTO project_field_value VALUES (118,97,100,1090,'90%','',90,'P');
INSERT INTO project_field_value VALUES (119,97,100,1095,'95%','',95,'P');
INSERT INTO project_field_value VALUES (120,97,100,1100,'100%','',100,'P');

#
# Priority (priority = 99)
#
INSERT INTO project_field_value VALUES (121,99,100,1,'1 - Lowest','',1,'P');
INSERT INTO project_field_value VALUES (122,99,100,2,'2','',2,'P');
INSERT INTO project_field_value VALUES (123,99,100,3,'3','',3,'P');
INSERT INTO project_field_value VALUES (124,99,100,4,'4','',4,'P');
INSERT INTO project_field_value VALUES (125,99,100,5,'5 - Medium','',5,'P');
INSERT INTO project_field_value VALUES (126,99,100,6,'6','',6,'P');
INSERT INTO project_field_value VALUES (127,99,100,7,'7','',7,'P');
INSERT INTO project_field_value VALUES (128,99,100,8,'8','',8,'P');
INSERT INTO project_field_value VALUES (129,99,100,9,'9 - Highest','',9,'P');

#
# Status (status_id = 100)
#
INSERT INTO project_field_value VALUES (130,100,100,100,'None','',1,'P');
INSERT INTO project_field_value VALUES (131,100,100,1,'Open','',2,'P');
INSERT INTO project_field_value VALUES (132,100,100,2,'Closed','',3,'P');
INSERT INTO project_field_value VALUES (133,100,100,3,'Deleted','',4,'P');
INSERT INTO project_field_value VALUES (134,100,100,4,'Suspended','',5,'P');

# ==============================
# Project field usage table
# ==============================
# Insert field usage information for group 100 (None). This will be
# the default pattern for all projects as long as they do not define
# their own settings

# Project task ID (new_project_task_id = 90)
#
INSERT INTO project_field_usage VALUES (90,100,1,1,1,10,NULL,NULL,NULL,NULL,NULL);

# Group Id (group_id = 91)
#
INSERT INTO project_field_usage VALUES (91,100,1,1,1,20,NULL,NULL,NULL,NULL,NULL);

# Created by (created_by = 92)
#
INSERT INTO project_field_usage VALUES (92,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL);

# Start date (start_date = 94)
#
INSERT INTO project_field_usage VALUES (94,100,1,1,1,40,NULL,NULL,NULL,NULL,NULL);

# Start date (end_date = 95)
#
INSERT INTO project_field_usage VALUES (95,100,1,1,1,50,NULL,NULL,NULL,NULL,NULL);

# Subproject (group_project_id = 96)
#
INSERT INTO project_field_usage VALUES (96,100,1,1,1,60,NULL,NULL,NULL,NULL,NULL);

# Percent complete (percent_complete = 97)
#
INSERT INTO project_field_usage VALUES (97,100,1,1,1,70,NULL,NULL,NULL,NULL,NULL);

# Effort (effort = 98)
#
INSERT INTO project_field_usage VALUES (98,100,1,1,1,80,NULL,NULL,NULL,NULL,NULL);

# Priority (priority = 99)
#
INSERT INTO project_field_usage VALUES (99,100,1,1,1,80,NULL,NULL,NULL,NULL,NULL);

# Status (status = 100)
#
INSERT INTO project_field_usage VALUES (100,100,1,1,1,90,NULL,NULL,NULL,NULL,NULL);

# Summary (summary = 102)
#
INSERT INTO project_field_usage VALUES (102,100,1,1,1,110,NULL,NULL,NULL,NULL,NULL);

# Original Submission (details = 103)
#
INSERT INTO project_field_usage VALUES (103,100,1,1,1,120,NULL,NULL,NULL,NULL,NULL);

# None value for project_type
#
INSERT INTO project_type VALUES (100,'None','None');



#****************************************************************
#*******       G E N E R I C   T R A C K E R S       ************
#*******               V  A  L  U  E  S              ************
#****************************************************************

--
-- Dumping data for table 'artifact_group_list'
--

INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions, instantiate_for_new_projects) VALUES (1, 100, 'Bugs', 'Bugs Tracker', 'bug', 1, 0, 0, '', NULL, NULL, 1);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions, instantiate_for_new_projects) VALUES (2, 100, 'Tasks', 'Tasks Tracker', 'task', 1, 0, 0, '', NULL, NULL, 1);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions, instantiate_for_new_projects) VALUES (3, 100, 'Support Requests', 'Support Requests Tracker', 'SR', 1, 1, 0, '', NULL, NULL, 1);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (4, 100, 'Empty', 'Empty Tracker', '', 1, 0, 0, '', NULL, NULL);

--
-- This tracker has the id 100 to force the next id to be greater than 100
-- 100 is a special value (None value)
--
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (100, 100, 'None', 'None', '', 0, 0, 0, '', NULL, NULL);

--
-- Dumping data for table 'artifact_field'
--


INSERT INTO artifact_field VALUES (7,1,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (6,1,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (5,1,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (1,1,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (4,1,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (3,1,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (2,1,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (8,1,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (10,1,'comment_type_id',2,'SB','','Comment Type','Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)','',0,1,0,1,NULL,'100');
INSERT INTO artifact_field VALUES (9,1,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (16,1,'resolution_id',2,'SB','','Resolution','How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (20,1,'bug_group_id',2,'SB','','Group','Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (2,2,'percent_complete',2,'SB','2','Percent complete','Percentage of completion','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (4,2,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','',0,1,1,0,NULL,'0.00');
INSERT INTO artifact_field VALUES (5,2,'start_date',4,'DF','','Start Date','Start Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (6,2,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (7,2,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (8,2,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (11,2,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (1,2,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (10,2,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (9,2,'multi_assigned_to',5,'MB','','Assigned to (multiple)','Who is in charge of this artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (12,2,'subproject_id',2,'SB','','Subproject','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (9,3,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (7,3,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (6,3,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (5,3,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (4,3,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,3,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (2,3,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (1,3,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');

INSERT INTO artifact_field VALUES (11,1,'category_version_id',2,'SB','','Component Version','The version of the System Component (aka Category) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (12,1,'platform_version_id',2,'SB','','Platform Version','The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (13,1,'reproducibility_id',2,'SB','','Reproducibility','How easy is it to reproduce the artifact','S',0,0,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (14,1,'size_id',2,'SB','','Size (loc)','The size of the code you need to develop or rework in order to fix the artifact','S',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (15,1,'fix_release_id',2,'SB','','Fixed Release','The release in which the artifact was actually fixed','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (17,1,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (18,1,'plan_release_id',2,'SB','','Planned Release','The release in which you initially planned the artifact to be fixed','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (19,1,'component_version',1,'TF','10/40','Component Version','Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (22,1,'priority',2,'SB','','Priority','How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (23,1,'keywords',1,'TF','60/120','Keywords','A list of comma separated keywords associated with a artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (24,1,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (26,1,'originator_name',1,'TF','20/40','Originator Name','The name of the person who reported the artifact (if different from the submitter field)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (27,1,'originator_email',1,'TF','20/40','Originator Email','Email address of the person who reported the artifact. Automatically included in the artifact email notification process.','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (28,1,'originator_phone',1,'TF','10/40','Originator Phone','Phone number of the person who reported the artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (29,1,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');

INSERT INTO artifact_field VALUES (13,2,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (14,2,'severity',2,'SB','','Priority','How quickly the artifact must be completed','',0,0,1,0,NULL,'5');

INSERT INTO artifact_field VALUES (10,3,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (11,3,'severity',2,'SB','','Priority','How quickly the artifact must be completed','',0,0,1,0,NULL,'5');

INSERT INTO artifact_field VALUES (1,4,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (2,4,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,4,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (4,4,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (5,4,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (6,4,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (7,4,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (8,4,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (9,4,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (10,4,'multi_assigned_to',5,'MB','','Assigned to (multiple)','Who is in charge of this artifact','',0,1,1,0,'artifact_technicians','100');
--
-- Dumping data for table 'artifact_field_usage'
--


INSERT INTO artifact_field_usage VALUES (7,1,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (6,1,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (5,1,1,1,1,900);
INSERT INTO artifact_field_usage VALUES (1,1,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (4,1,1,1,1,50);
INSERT INTO artifact_field_usage VALUES (3,1,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (2,1,1,0,0,60);
INSERT INTO artifact_field_usage VALUES (8,1,1,1,1,20);
INSERT INTO artifact_field_usage VALUES (10,1,1,0,0,NULL);
INSERT INTO artifact_field_usage VALUES (9,1,1,1,1,1000);
INSERT INTO artifact_field_usage VALUES (16,1,1,0,0,40);
INSERT INTO artifact_field_usage VALUES (20,1,1,1,1,30);
INSERT INTO artifact_field_usage VALUES (2,2,1,1,1,20);
INSERT INTO artifact_field_usage VALUES (3,2,1,1,1,30);
INSERT INTO artifact_field_usage VALUES (4,2,1,1,1,40);
INSERT INTO artifact_field_usage VALUES (5,2,1,1,1,60);
INSERT INTO artifact_field_usage VALUES (6,2,1,1,1,80);
INSERT INTO artifact_field_usage VALUES (7,2,1,1,1,900);
INSERT INTO artifact_field_usage VALUES (8,2,1,1,1,1000);
INSERT INTO artifact_field_usage VALUES (11,2,1,0,0,50);
INSERT INTO artifact_field_usage VALUES (1,2,1,1,1,1);
INSERT INTO artifact_field_usage VALUES (10,2,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (9,3,1,1,1,NULL);
INSERT INTO artifact_field_usage VALUES (7,3,1,0,0,30);
INSERT INTO artifact_field_usage VALUES (6,3,1,0,0,20);
INSERT INTO artifact_field_usage VALUES (5,3,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (4,3,1,1,1,5);
INSERT INTO artifact_field_usage VALUES (3,3,1,1,1,1000);
INSERT INTO artifact_field_usage VALUES (2,3,1,1,1,900);
INSERT INTO artifact_field_usage VALUES (1,3,1,1,1,1);
INSERT INTO artifact_field_usage VALUES (9,2,1,1,1,70);
INSERT INTO artifact_field_usage VALUES (12,2,1,1,1,10);

INSERT INTO artifact_field_usage VALUES (11,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (12,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (13,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (14,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (15,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (17,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (18,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (19,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (22,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (23,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (24,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (26,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (27,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (28,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (29,1,0,0,0,0);

INSERT INTO artifact_field_usage VALUES (13,2,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (14,2,1,1,1,30);

INSERT INTO artifact_field_usage VALUES (10,3,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (11,3,1,1,1,40);

INSERT INTO artifact_field_usage VALUES (1,4,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (2,4,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (3,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (4,4,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (5,4,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (6,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (7,4,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (8,4,1,1,1,20);
INSERT INTO artifact_field_usage VALUES (9,4,1,0,1,30);
INSERT INTO artifact_field_usage VALUES (10,4,0,0,1,40);

--
-- Dumping data for table 'artifact_field_value_list'
--


INSERT INTO artifact_field_value_list VALUES (2,1,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (2,1,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (2,1,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (3,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,9,'9 - Critical','',90,'P');
INSERT INTO artifact_field_value_list VALUES (10,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (16,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (16,1,1,'Fixed','The bug was resolved',20,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,2,'Invalid','The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)',30,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,3,'Wont Fix','The bug won''t be fixed (probably because it is very minor)',40,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,4,'Later','The bug will be fixed later (no date given)',50,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,5,'Remind','The bug will be fixed later but keep in the remind state for easy identification',60,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,6,'Works for me','The project team was unable to reproduce the bug',70,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,7,'Duplicate','This bug is already covered by another bug description (see related bugs list)',80,'A');
INSERT INTO artifact_field_value_list VALUES (11,2,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (11,2,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (11,2,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (12,2,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (2,2,1095,'95%','',95,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1090,'90%','',90,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1085,'85%','',85,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1080,'80%','',80,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1075,'75%','',75,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1070,'70%','',70,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1065,'65%','',65,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1060,'60%','',60,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1055,'55%','',55,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1050,'50%','',50,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1045,'45%','',45,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1040,'40%','',40,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1035,'35%','',35,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1030,'30%','',30,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1025,'25%','',25,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1020,'20%','',20,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1015,'15%','',15,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1010,'10%','',10,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1000,'Not started','',0,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1100,'100%','',100,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (5,3,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,3,1095,'95%','',95,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1090,'90%','',90,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1085,'85%','',85,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1080,'80%','',80,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1075,'75%','',75,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1070,'70%','',70,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1065,'65%','',65,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1060,'60%','',60,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1055,'55%','',55,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1050,'50%','',50,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1045,'45%','',45,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1040,'40%','',40,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1035,'35%','',35,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1030,'30%','',30,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1025,'25%','',25,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1020,'20%','',20,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1015,'15%','',15,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1010,'10%','',10,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1000,'Not started','',0,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1100,'100%','',100,'A');

INSERT INTO artifact_field_value_list VALUES (11,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (12,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (13,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (14,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (15,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (18,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (20,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (22,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (24,1,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (14,2,1,'1 - Lowest','',10,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,5,'5 - Medium','',50,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,9,'9 - Highest','',90,'P');

INSERT INTO artifact_field_value_list VALUES (11,3,1,'1 - Lowest','',10,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,5,'5 - Medium','',50,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,9,'9 - Highest','',90,'P');

INSERT INTO artifact_field_value_list VALUES (6,4,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (6,4,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (6,4,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (7,4,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,9,'9 - Critical','',90,'P');

--
-- Dumping data for table 'artifact_report'
--


INSERT INTO artifact_report VALUES (100,100,100,'Default','The system default artifact report','S');
INSERT INTO artifact_report VALUES (2,2,100,'Tasks','Tasks Report','P');
INSERT INTO artifact_report VALUES (3,3,100,'SR','Support Requests Report','P');
INSERT INTO artifact_report VALUES (4,1,100,'Bugs','Bugs Report','P');


--
-- Dumping data for table 'artifact_report_field'
--

INSERT INTO artifact_report_field VALUES (100,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (100,'assigned_to',1,1,30,40,NULL);
INSERT INTO artifact_report_field VALUES (100,'status_id',1,0,20,NULL,NULL);
INSERT INTO artifact_report_field VALUES (100,'artifact_id',1,1,50,10,NULL);
INSERT INTO artifact_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (100,'open_date',1,1,40,30,NULL);
INSERT INTO artifact_report_field VALUES (100,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (100,'severity',0,0,NULL,NULL,NULL);
INSERT INTO artifact_report_field VALUES (2,'subproject_id',1,1,10,30,NULL);
INSERT INTO artifact_report_field VALUES (2,'multi_assigned_to',1,1,20,60,NULL);
INSERT INTO artifact_report_field VALUES (2,'status_id',1,1,30,100,NULL);
INSERT INTO artifact_report_field VALUES (2,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (2,'start_date',0,1,NULL,40,NULL);
INSERT INTO artifact_report_field VALUES (2,'close_date',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (2,'hours',0,1,NULL,70,NULL);
INSERT INTO artifact_report_field VALUES (2,'percent_complete',0,1,NULL,80,NULL);
INSERT INTO artifact_report_field VALUES (2,'artifact_id',0,1,NULL,1,NULL);
INSERT INTO artifact_report_field VALUES (3,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'status_id',1,0,30,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (3,'open_date',0,1,NULL,30,NULL);
INSERT INTO artifact_report_field VALUES (3,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (3,'severity',0,0,NULL,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (3,'assigned_to',1,1,20,40,NULL);
INSERT INTO artifact_report_field VALUES (4,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (4,'assigned_to',1,1,30,40,NULL);
INSERT INTO artifact_report_field VALUES (4,'status_id',1,0,40,NULL,NULL);
INSERT INTO artifact_report_field VALUES (4,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (4,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (4,'open_date',0,1,NULL,30,NULL);
INSERT INTO artifact_report_field VALUES (4,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (4,'bug_group_id',1,0,20,NULL,NULL);


# ==============================
# artifact_notification_role table
# ==============================
# Create the list of roles a user can play wrt to a artifact
#
INSERT INTO artifact_notification_role_default VALUES (1,'SUBMITTER','Submitter', 'The person who submitted the artifact',10);
INSERT INTO artifact_notification_role_default VALUES (2,'ASSIGNEE','Assignee','The person to whom the artifact was assigned',20);
INSERT INTO artifact_notification_role_default VALUES (3,'CC','CC','The person who is in the CC list',30);
INSERT INTO artifact_notification_role_default VALUES (4,'COMMENTER','Commenter','A person who once posted a follow-up comment',40);

# ==============================
# artifact_notification_event table
# ==============================
# Create the list of events that can occur in a artifact update
#
INSERT INTO artifact_notification_event_default VALUES (1,'ROLE_CHANGE','Role has changed','I\'m added to or removed from this role',10);
INSERT INTO artifact_notification_event_default VALUES (2,'NEW_COMMENT','New comment','A new followup comment is added',20);
INSERT INTO artifact_notification_event_default VALUES (3,'NEW_FILE','New attachment','A new file attachment is added',30);
INSERT INTO artifact_notification_event_default VALUES (4,'CC_CHANGE','CC Change','A new CC address is added/removed',40);
INSERT INTO artifact_notification_event_default VALUES (5,'CLOSED','artifact closed','The artifact is closed',50);
INSERT INTO artifact_notification_event_default VALUES (6,'PSS_CHANGE','PSS change','Priority,Status,Severity changes',60);
INSERT INTO artifact_notification_event_default VALUES (7,'ANY_OTHER_CHANGE','Any other Changes','Any changes not mentioned above',70);
INSERT INTO artifact_notification_event_default VALUES (8,'I_MADE_IT','I did it','I am the author of the change',80);
INSERT INTO artifact_notification_event_default VALUES (9,'NEW_ARTIFACT','New artifact','A new artifact has been submitted',90);


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
INSERT INTO snippet_license VALUES (1,'Code eXchange Policy');
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


--
-- Dumping data for table 'service'
--

-- Group 100 (templates)
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (1, 100, 'Summary', 'Project Summary', 'summary', '/projects/$projectname/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (2, 100, 'Admin', 'Project Administration', 'admin', '/project/admin/?group_id=$group_id', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (3, 100, 'Home Page', 'Project Home Page', 'homepage', 'http://$projectname.$sys_default_domain', 1, 1, 'system', 30);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (4, 100, 'Forums', 'Project Forums', 'forum', '/forum/?group_id=$group_id', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (5, 100, 'Bugs', 'Bug Tracking System', 'bugs', '/bugs/?group_id=$group_id', 0, 0, 'system', 50);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (6, 100, 'Support', 'Support Request Manager', 'support', '/support/?group_id=$group_id', 0, 0, 'system', 60);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (7, 100, 'Patches', 'Patch Manager', 'patch', '/patch/?group_id=$group_id', 1, 1, 'system', 70);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (8, 100, 'Lists', 'Mailing Lists', 'mail', '/mail/?group_id=$group_id', 1, 1, 'system', 80);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (9, 100, 'Tasks', 'Task Manager', 'task', '/pm/?group_id=$group_id', 0, 0, 'system', 90);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (10, 100, 'Docs', 'Document Manager', 'doc', '/docman/?group_id=$group_id', 1, 1, 'system', 100);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (11, 100, 'Surveys', 'Project Surveys', 'survey', '/survey/?group_id=$group_id', 1, 1, 'system', 110);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (12, 100, 'News', 'Project News', 'news', '/news/?group_id=$group_id', 1, 1, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (13, 100, 'CVS', 'CVS Access', 'cvs', '/cvs/?group_id=$group_id', 1, 1, 'system', 130);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (14, 100, 'Files', 'File Releases', 'file', '/file/showfiles.php?group_id=$group_id', 1, 1, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (15, 100, 'Trackers', 'Project Trackers', 'tracker', '/tracker/index.php?group_id=$group_id', 1, 1, 'system', 150);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (16, 100, 'Subversion', 'Subversion Access', 'svn', '/svn/?group_id=$group_id', 1, 1, 'system', 135);

-- Group 1
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (31, 1, 'Summary', 'Project Summary', 'summary', '/projects/codex/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (32, 1, 'Admin', 'Project Administration', 'admin', '/project/admin/?group_id=1', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (33, 1, 'Home Page', 'Project Home Page', 'homepage', 'http://codex._DOMAIN_NAME_', 1, 1, 'system', 30);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (34, 1, 'Forums', 'Project Forums', 'forum', '/forum/?group_id=1', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (35, 1, 'Patches', 'Patch Manager', 'patch', '/patch/?group_id=1', 1, 1, 'system', 70);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (36, 1, 'Lists', 'Mailing Lists', 'mail', '/mail/?group_id=1', 1, 1, 'system', 80);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (37, 1, 'Docs', 'Document Manager', 'doc', '/docman/?group_id=1', 1, 1, 'system', 100);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (38, 1, 'Surveys', 'Project Surveys', 'survey', '/survey/?group_id=1', 1, 1, 'system', 110);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (39, 1, 'News', 'Project News', 'news', '/news/?group_id=1', 1, 1, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (40, 1, 'CVS', 'CVS Access', 'cvs', '/cvs/?group_id=1', 1, 1, 'system', 130);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (41, 1, 'Files', 'File Releases', 'file', '/file/showfiles.php?group_id=1', 1, 1, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (42, 1, 'Trackers', 'Project Trackers', 'tracker', '/tracker/index.php?group_id=1', 1, 1, 'system', 150);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (43, 1, 'Subversion', 'Subversion Access', 'svn', '/svn/?group_id=1', 1, 1, 'system', 135);

-- Group 46 - SiteNews
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (51, 46, 'Summary', 'Project Summary', 'summary', '/projects/sitenews/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (52, 46, 'Admin', 'Project Administration', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (53, 46, 'Home Page', 'Project Home Page', 'homepage', 'http://sitenews._DOMAIN_NAME_', 1, 1, 'system', 30);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (54, 46, 'Forums', 'Project Forums', 'forum', '/forum/?group_id=46', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (55, 46, 'News', 'Project News', 'news', '/news/?group_id=46', 1, 1, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (56, 46, 'Files', 'File Releases', 'file', '/file/showfiles.php?group_id=46', 1, 1, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (57, 46, 'Trackers', 'Project Trackers', 'tracker', '/tracker/index.php?group_id=46', 1, 1, 'system', 150);

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

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (100, "nobody", "Empty Group", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (1, "anonymous_users", "Anonymous Users", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (2, "registered_users", "Registered CodeX Users", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (3, "project_members", "Project Members", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (4, "project_admins", "Project Administrators", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (10, "document_editor", "Document Editors", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (11, "file_manager_admin", "File Manager Administrators", 100);
-- Not used yet
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (12, "forum_moderator", "Forum Moderators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (13, "patch_tech", "Patch Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (14, "patch_admins", "Patch Administrators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (15, "tracker_tech", "Tracker Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (16, "tracker_admins", "Tracker Administrators", 100);
--
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (30, "legacy_sr_tech", "Support Request Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (31, "legacy_sr_admins", "Support Request Administrators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (32, "legacy_task_tech", "Task Manager Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (33, "legacy_task_admins", "Task Manager Administrators", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (34, "legacy_bug_tech", "Bug Tracker Technicians", 100);
-- INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (35, "legacy_bug_admins", "Bug Tracker Administrators", 100);



---
--- Set permissions_values entries. These should normally be set at installation time only.
-- ugroup_id should always be lower than 100.
---
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PACKAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PACKAGE_READ',11);

-- No default value for RELEASE_READ -> use parent permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',11);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('DOCUMENT_READ',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('RELEASE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',10);


