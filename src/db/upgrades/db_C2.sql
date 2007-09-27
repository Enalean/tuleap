# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2005. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
# Description
# Database upgrade script for project C2 (tracker/field permissions)
#
#
#
#


--
-- Create new columns allow_copy for the artifact duplication
--
ALTER TABLE artifact_group_list ADD COLUMN allow_copy int(11) DEFAULT 0 NOT NULL AFTER allow_anon;


--
-- Create new columns hide_members
--
ALTER TABLE groups ADD COLUMN hide_members int(11) DEFAULT '0' NOT NULL AFTER rand_hash;


--
-- Create new automatic ugroups
--
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (15, "tracker_admins", "Tracker Administrators", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (16, "tracker_techs", "Tracker Technicians", 100);


--
-- Create default access permissions for trackers and fields
--
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',16);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',16);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',100);
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',2);
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',3);
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',4);
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',16);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ACCESS_FULL',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',16);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',16);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',16);


-- Bugs
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','1',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#3',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#4',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#5',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#8',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#9',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#20',2);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#13',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#14',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#15',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#16',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#17',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#18',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#19',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#20',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#22',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#23',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#24',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#26',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#27',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#28',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#29',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#2',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#3',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#4',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#5',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#8',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#9',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#10',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#11',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#12',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#13',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#14',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#15',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#16',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#17',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#18',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#19',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#20',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#22',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#23',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#24',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#26',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#27',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#28',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#29',16);

-- Tasks
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','2',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#2',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#5',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#6',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#12',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#14',3);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#13',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#14',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#2',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#4',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#5',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#6',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#7',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#8',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#9',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#11',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#12',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#14',16);

-- SR
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','3',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#11',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#11',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#2',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#3',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#5',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#6',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#7',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#10',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#11',16);

-- Empty
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','4',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#10',3);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#10',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#3',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#4',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#6',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#7',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#8',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#9',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#10',16);


-- Patch
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','5',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#3',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#5',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#7',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#8',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#10',2);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#12',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#3',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#5',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#6',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#7',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#8',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#9',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#10',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#11',16);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#12',16);


