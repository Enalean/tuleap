# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
#
# Database upgrade script 
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Remove permissions submitter/assignee for registered_users. Replace them by full if set.
#
#
# References:
# no
#
# Dependencies:
# no
#
# 
# SQL script comes next...
#

DELETE FROM permissions_values
WHERE permission_type = 'TRACKER_ACCESS_SUBMITTER' 
AND ugroup_id = '2';
 
DELETE FROM permissions_values
WHERE permission_type = 'TRACKER_ACCESS_ASSIGNEE' 
AND ugroup_id = '2';

INSERT INTO permissions(object_id, ugroup_id, permission_type) SELECT DISTINCT object_id, 2, 'TRACKER_ACCESS_FULL'
FROM permissions
WHERE ugroup_id = 2
AND permission_type = 'TRACKER_ACCESS_ASSIGNEE' OR permission_type = 'TRACKER_ACCESS_SUBMITTER';

DELETE FROM permissions
WHERE ugroup_id = 2
AND permission_type = 'TRACKER_ACCESS_ASSIGNEE' OR permission_type = 'TRACKER_ACCESS_SUBMITTER';

