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
-- Create new automatic ugroup
--
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (15, "tracker_admins", "Tracker Administrators", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (16, "tracker_techs", "Tracker Technicians", 100);

--
-- Create default access permissions for trackers and fields
--
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_FIELD_SUBMIT',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',16);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_FIELD_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',16);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_FIELD_UPDATE',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',16);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ACCESS_FULL',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',16);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',16);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',15);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',16);



--
-- Create new columns allow_copy and copy_prefix for the artifact duplication
--
ALTER TABLE artifact_group_list ADD COLUMN allow_copy int(11) DEFAULT 0 NOT NULL AFTER allow_anon;
