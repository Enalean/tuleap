#
# SourceForge: Breaking Down the Barriers to Open Source Development
# Copyright 1999-2000 (c) The SourceForge Crew
# http://sourceforge.net
#

#
# Default data for table 'bug_resolution'
#

INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (1,'Fixed');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (2,'Invalid');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (3,'Wont Fix');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (4,'Later');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (5,'Remind');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (6,'Works For Me');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (100,'None');
INSERT INTO bug_resolution (resolution_id, resolution_name) VALUES (101,'Duplicate');

#
# Default data for table 'bug_status'
#

INSERT INTO bug_status (status_id, status_name) VALUES (1,'Open');
INSERT INTO bug_status (status_id, status_name) VALUES (3,'Closed');
INSERT INTO bug_status (status_id, status_name) VALUES (100,'None');

#
# Default data for table 'patch_category'
#

INSERT INTO patch_category (patch_category_id, group_id, category_name) VALUES (100,0,'None');

#
# Default data for table 'patch_status'
#

INSERT INTO patch_status (patch_status_id, status_name) VALUES (1,'Open');
INSERT INTO patch_status (patch_status_id, status_name) VALUES (2,'Closed');
INSERT INTO patch_status (patch_status_id, status_name) VALUES (3,'Deleted');
INSERT INTO patch_status (patch_status_id, status_name) VALUES (4,'Postponed');
INSERT INTO patch_status (patch_status_id, status_name) VALUES (100,'None');

#
# Default data for table 'project_status'
#

INSERT INTO project_status (status_id, status_name) VALUES (1,'Open');
INSERT INTO project_status (status_id, status_name) VALUES (2,'Closed');
INSERT INTO project_status (status_id, status_name) VALUES (100,'None');
INSERT INTO project_status (status_id, status_name) VALUES (3,'Deleted');

#
# Default Data for 'survey_question_types'
#

INSERT INTO survey_question_types (id, type) VALUES (1,'Radio Buttons 1-5');
INSERT INTO survey_question_types (id, type) VALUES (2,'Text Area');
INSERT INTO survey_question_types (id, type) VALUES (3,'Radio Buttons Yes/No');
INSERT INTO survey_question_types (id, type) VALUES (4,'Comment Only');
INSERT INTO survey_question_types (id, type) VALUES (5,'Text Field');
INSERT INTO survey_question_types (id, type) VALUES (100,'None');

INSERT INTO user (user_id, user_name, email, user_pw, realname, status, shell,
unix_pw, unix_status, unix_uid, unix_box, add_date, confirm_hash, mail_siteupdates,
mail_va, authorized_keys, email_new)  VALUES (100,'None','noreply@sourceforge.net','*********34343','0','S','0','0','0',0,'0',940000000,NULL,1,0,NULL,NULL);

#
# Default data for Support System
#
INSERT INTO support (support_id) VALUES ('100000');

INSERT INTO support_messages (support_message_id,support_id) VALUES ('100000','100000');

INSERT INTO support_canned_responses (support_canned_id) VALUES ('100000');

insert into support_status values('1','Open');
insert into support_status values('2','Closed');
insert into support_status values('3','Deleted');

insert into support_category VALUES ('100','0','None');
insert into support_category VALUES ('10000','0','None');

#
# Default data for Help Wanted System
#

INSERT INTO people_skill_year VALUES ('','< 6 Months');
INSERT INTO people_skill_year VALUES ('','6 Mo - 2 yr');
INSERT INTO people_skill_year VALUES ('','2 yr - 5 yr');
INSERT INTO people_skill_year VALUES ('','5 yr - 10 yr');
INSERT INTO people_skill_year VALUES ('','> 10 years');

INSERT INTO people_skill_level VALUES ('','Want to Learn');
INSERT INTO people_skill_level VALUES ('','Competent');
INSERT INTO people_skill_level VALUES ('','Wizard');
INSERT INTO people_skill_level VALUES ('','Wrote The Book');
INSERT INTO people_skill_level VALUES ('','Wrote It');

INSERT INTO people_job_category VALUES ('','Developer');
INSERT INTO people_job_category VALUES ('','Project Manager');
INSERT INTO people_job_category VALUES ('','Unix Admin');
INSERT INTO people_job_category VALUES ('','Doc Writer');
INSERT INTO people_job_category VALUES ('','Tester');
INSERT INTO people_job_category VALUES ('','Support Manager');
INSERT INTO people_job_category VALUES ('','Graphic/Other Designer');

INSERT INTO people_job_status VALUES ('1','Open');
INSERT INTO people_job_status VALUES ('2','Filled');
INSERT INTO people_job_status VALUES ('3','Deleted');

#
#  DEfault data for group_type
#
INSERT INTO group_type VALUES ('1','Project');
INSERT INTO group_type VALUES ('2','Foundry');

##
## Default data for new filerelease system
##
INSERT INTO frs_filetype VALUES ('1000','.deb');
INSERT INTO frs_filetype VALUES ('2000','.rpm');
INSERT INTO frs_filetype VALUES ('3000','.zip');
INSERT INTO frs_filetype VALUES ('','.bz2');
INSERT INTO frs_filetype VALUES ('','.gz');
INSERT INTO frs_filetype VALUES ('5000','Source .zip');
INSERT INTO frs_filetype VALUES ('','Source .bz2');
INSERT INTO frs_filetype VALUES ('','Source .gz');
INSERT INTO frs_filetype VALUES ('5100','Source .rpm');
INSERT INTO frs_filetype VALUES ('5900','Other Source File');
INSERT INTO frs_filetype VALUES ('8000','.jpg');
INSERT INTO frs_filetype VALUES ('','text');
INSERT INTO frs_filetype VALUES ('','html');
INSERT INTO frs_filetype VALUES ('','pdf');
INSERT INTO frs_filetype VALUES ('9999','Other');

INSERT INTO frs_status VALUES ('1','Active');
INSERT INTO frs_status VALUES ('3','Hidden');

INSERT INTO frs_processor VALUES ('1000','i386');
INSERT INTO frs_processor VALUES ('6000','IA64');
INSERT INTO frs_processor VALUES ('7000','Alpha');
INSERT INTO frs_processor VALUES ('8000','Any');
INSERT INTO frs_processor VALUES ('2000','PPC');
INSERT INTO frs_processor VALUES ('3000','MIPS');
INSERT INTO frs_processor VALUES ('4000','Sparc');
INSERT INTO frs_processor VALUES ('5000','UltraSparc');
INSERT INTO frs_processor VALUES ('9999','Other');



##
## EOF
##
