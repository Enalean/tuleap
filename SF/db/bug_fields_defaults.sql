#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
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
#    Insert default values in the new tables of the new BTS and also
#    transfer legacy groups and categories into the new bug_field_value
#    table. Finally delete the useless legacy tables.
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
  VALUES (90,'bug_id','TF','','Bug ID','Unique bug identifier','S',1,0,0,1);
INSERT INTO bug_field \
  VALUES (91,'group_id','TF','','Group ID','Unique project identifier','S',1,0,0,1);
INSERT INTO bug_field \
  VALUES (92,'submitted_by','TF','','Submitted by','User who originally submitted the bug','S',1,0,0,1);
INSERT INTO bug_field \
  VALUES (93,'date','TF','','Submitted on','Date and time for the initial bug submission','S',1,0,0,1);
INSERT INTO bug_field \
  VALUES (94,'close_date','TF','','Closed on','Date and time when the bug status was changed to ''Closed''','S',1,0,0,1);
INSERT INTO bug_field \
  VALUES (101,'status_id','SB','','Status','Bug Status','P',1,0,1,0);
INSERT INTO bug_field \
  VALUES (102,'severity','SB','','Severity','Impact of the bug on the system (Critical, Major,...)','S',1,0,1,0);
INSERT INTO bug_field \
  VALUES (103,'category_id','SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','P',0,0,1,0);
INSERT INTO bug_field \
  VALUES (104,'assigned_to','SB','','Assigned to','Who is in charge of solving the bug','S',1,0,1,0);
INSERT INTO bug_field \
  VALUES (105,'summary','TF','60/120','Summary','One line description of the bug','S',1,0,1,1);
INSERT INTO bug_field \
  VALUES (106,'details','TA','60/7','Original Submission','A full description of the bug','S',1,1,1,1);
INSERT INTO bug_field \
  VALUES (107,'bug_group_id','SB','','Bug Group','Characterizes the nature of the bug (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...','P',0,0,1,0);
INSERT INTO bug_field \
  VALUES (108,'resolution_id','SB','','Resolution','How you have decided to fix the bug (Fixed, Work for me, Duplicate,..)','S',1,0,1,0);
#
# -----------------------------
#
# New Extension fields second
#
INSERT INTO bug_field \
  VALUES (200,'category_version_id','SB','','Component Version','The version of the System Component (aka Bug Category) impacted by the bug','P',0,0,1,0);
INSERT INTO bug_field \
  VALUES (201,'platform_version_id','SB','','Platform Version','The name and version of the platform your software was running on when the bug occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)','P',0,0,1,0);
INSERT INTO bug_field \
  VALUES (202,'reproducibility_id','SB','','Reproducibility','How easy is it to reproduce the bug','S',0,0,1,0);
INSERT INTO bug_field \
  VALUES (203,'size_id','SB','','Size (loc)','The size of the code you need to develop or rework in order to fix the bug','S',0,0,1,0);
INSERT INTO bug_field \
  VALUES (204,'fix_release_id','SB','','Fixed Release','The release in which the bug was actually fixed','P',0,0,1,0);
INSERT INTO bug_field \
  VALUES (205,'comment_type_id','SB','','Comment Type','Specify the nature of the  follow up comment attached to this bug (Workaround, Test Case, Impacted Files,...)','P',1,0,0,1);
INSERT INTO bug_field \
  VALUES (206,'hours','TF','5/5','Effort','Number of hours of work needed to fix the bug (including testing)','S',0,0,1,0);
INSERT INTO bug_field \
  VALUES (207,'plan_release_id','SB','','Planned Release','The release in which you initially planned the bug to be fixed','P',0,0,1,0);
INSERT INTO bug_field \
  VALUES (208,'component_version','TF','10/40','Component Version','Version of the system component (or work product) impacted by the bug. Same as the other Component Version field <u>except</u> this one is free text.','S',0,0,1,0);
INSERT INTO bug_field \
  VALUES (209,'fix_release','TF','10/40','Fixed Release','The release in which the bug was actually fixed. Same as the other Fixed Release field <u>except</u> this one is free text.','S',0,0,1,0);
INSERT INTO bug_field \
  VALUES (210,'plan_release','TF','10/40','Planned Release','The release in which you initially planned the bug to be fixed. Same as the other Planned Release field <u>except</u> this one is free text.','S',0,0,1,0);
INSERT INTO bug_field \
  VALUES (211,'priority','SB','','Priority','How quickly the bug must be fixed (Immediate, Normal, Low, Later,...)','S',0,0,1,0);


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
INSERT INTO bug_field_usage VALUES (90,100,1,0,0,10);

# Group ID (bug_field_id = 91)
#
INSERT INTO bug_field_usage VALUES (91,100,1,1,1,30);

# Submitted by (bug_field_id = 92)
#
INSERT INTO bug_field_usage VALUES (92,100,1,0,0,20);

# Submitted on (bug_field_id = 93)
#
INSERT INTO bug_field_usage VALUES (93,100,1,0,0,40);

# Close Date (bug_field_id = 94)
#
INSERT INTO bug_field_usage VALUES (94,100,1,0,0,50);

# Status (bug_field_id = 101)
#
INSERT INTO bug_field_usage VALUES (101,100,1,0,0,600);

# Severity (bug_field_id = 102)
#
INSERT INTO bug_field_usage VALUES (102,100,1,0,1,200);

# Category (bug_field_id = 103)
#
INSERT INTO bug_field_usage VALUES (103,100,1,1,1,100);

# Assigned_to (bug_field_id = 104)
#
INSERT INTO bug_field_usage VALUES (104,100,1,0,1,500);

# Summary (bug_field_id = 105)
#
INSERT INTO bug_field_usage VALUES (105,100,1,1,1,700000);

# Details (bug_field_id = 106)
#
INSERT INTO bug_field_usage VALUES (106,100,1,1,1,700001);

# Group - bug group (bug_field_id = 107)
#
INSERT INTO bug_field_usage VALUES (107,100,1,1,1,300);

# Resolution (bug_field_id = 108)
#
INSERT INTO bug_field_usage VALUES (108,100,1,0,0,400);

# category version id (bug_field_id = 200)
#
INSERT INTO bug_field_usage VALUES (200,100,0,0,0,1000);

# platform version (bug_field_id = 201)
#
INSERT INTO bug_field_usage VALUES (201,100,0,0,0,1100);

# reproducibility (bug_field_id = 202)
#
INSERT INTO bug_field_usage VALUES (202,100,0,0,0,1200);

# size (bug_field_id = 203)
#
INSERT INTO bug_field_usage VALUES (203,100,0,0,0,1300);

# fixed release (bug_field_id = 204)
#
INSERT INTO bug_field_usage VALUES (204,100,0,0,0,1400);

# comment type (bug_field_id = 205)
#
INSERT INTO bug_field_usage VALUES (205,100,1,0,0,1500);

# hours (bug_field_id = 206)
#
INSERT INTO bug_field_usage VALUES (206,100,0,0,0,1700);

# planned release (bug_field_id = 207)
#
INSERT INTO bug_field_usage VALUES (207,100,0,0,0,1600);

# component version string (bug_field_id = 208)
#
INSERT INTO bug_field_usage VALUES (208,100,0,0,0,1800);

# fixed release string (bug_field_id = 209)
#
INSERT INTO bug_field_usage VALUES (209,100,0,0,0,1900);

# planned release string (bug_field_id = 210)
#
INSERT INTO bug_field_usage VALUES (210,100,0,0,0,2000);

# priority (bug_field_id = 211)
#
INSERT INTO bug_field_usage VALUES (211,100,0,0,0,250);

#**********************************
# TRANSFER LEGACY FIELD VALUES
#**********************************

# ==============================
# Bug_history table
# ==============================
# Set all comment type to None for existing details field of the
# bug_history table
#
UPDATE bug_history SET type='100' WHERE field_name='details';

# ==============================
# Bug_group table
# ==============================
# Transfer legacy values into the new bug_field_value table
# (use the old bug_group_id as order_id to preserve the value order on screen)
#
INSERT INTO bug_field_value (bug_field_id,group_id,value_id,value,order_id,status) \
SELECT '107',group_id,bug_group_id,group_name,bug_group_id,'A' FROM bug_group \
WHERE group_id <> 100;

# Insert the None value for all groups
INSERT INTO bug_field_value (bug_field_id,group_id,value_id,value,order_id,status) \
SELECT DISTINCT '107',group_id,'100','None','100','A' FROM bug_group \
WHERE group_id <> 100;



# ==============================
# Bug_category table
# ==============================
# Transfer legacy values into the new bug_field_value table
# (use the old bug_catgeory_id as order_id to preserve the value order on screen)
#
INSERT INTO bug_field_value (bug_field_id,group_id,value_id,value,order_id,status) \
SELECT '103',group_id,bug_category_id,category_name,bug_category_id,'A' FROM bug_category \
WHERE group_id <> 100;

# Insert the None value for all groups
INSERT INTO bug_field_value (bug_field_id,group_id,value_id,value,order_id,status) \
SELECT DISTINCT '103',group_id,'100','None','100','A' FROM bug_category \
WHERE group_id <> 100;

#
# Get read of the old unused tables
DROP TABLE IF EXISTS bug_group, bug_category, bug_resolution, bug_status;


# ==============================
# Bug_profile table
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
