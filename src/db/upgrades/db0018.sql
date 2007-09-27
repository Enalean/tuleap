# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
#
# $Id$
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. 
#
# Description
# Add support for service bar configuration
#
#
# References:
# Task #176 on CodeX
#
# Dependencies:
# ... if other dbXXXX.sql files must be applied before that one list them
# here
#
# 
# SQL script comes next...
#

CREATE TABLE service (
	service_id int(11) NOT NULL auto_increment,
	group_id int(11) NOT NULL,
	label text,
	description text,
	short_name text,
	link text,
	is_active int(11) DEFAULT 0 NOT NULL,
	is_used int(11) DEFAULT 0 NOT NULL,
        scope text NOT NULL,
        rank int(11) NOT NULL default '0',
	primary key (service_id),
        key idx_group_id(group_id)
);

--
-- Dumping data for table 'service'
--

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
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (14, 100, 'Files', 'File Releases', 'file', '/project/filelist.php?group_id=$group_id', 1, 1, 'system', 140);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (15, 100, 'Trackers', 'Project Trackers', 'tracker', '/tracker/index.php?group_id=$group_id', 1, 1, 'system', 150);
--
-- This service has the id 100 to force the next id to be greater than 100
-- 100 is a special value (None value)
--
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (100, 0, 'None', 'None', '', '', 0, 0, 'project', 0);


