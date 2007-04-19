# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0020.sql 990 2004-04-29 08:33:02Z schneide $
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Add fields assigned_to and multi_assigned_to for the Empty Artifact into artifact_field and artifact_field_usage table 
# Change usage of the severity field of the Empty Artifact. Allow it to be shown when a new artifact is created
# Add the default value 'None' to the field Group in the Bug template
#
#
# References:
# See bug #375
#
# Dependencies:
# none
#
#

## Add fields assigned_to and multi_assigned_to for the Empty Artifact into artifact_field and artifact_field_usage table 
INSERT INTO artifact_field (field_id,group_artifact_id,field_name,data_type,display_type,display_size,label,description,scope,required,empty_ok,keep_history,special,value_function,default_value) VALUES (9,4,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');

INSERT INTO artifact_field VALUES (10,4,'multi_assigned_to',5,'MB','','Assigned to (multiple)','Who is in charge of this artifact','',0,1,1,0,'artifact_technicians','100');


INSERT INTO artifact_field_usage VALUES (9,4,1,0,1,30);
INSERT INTO artifact_field_usage VALUES (10,4,0,0,1,40);

## Change usage of the severity field of the Empty Artifact. Allow it to be shown when a new artifact is created
UPDATE artifact_field_usage SET show_on_add_members=1 WHERE field_id=7 AND group_artifact_id=4;

## Add the default value 'None' to the field Group in the Bug template
INSERT INTO artifact_field_value_list VALUES (20,1,100,'None','',10,'P');

## Change the default value of the multi_assigned_to field of the Task Tracker Template to None (=100)
UPDATE artifact_field SET default_value = '100' where group_artifact_id = 2 and field_id = 9;