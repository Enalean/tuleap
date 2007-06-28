# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# 
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the
# CodeX source code. All references are included
# below.
#
# Description
# A bit of cleaning in the artifact_fields:
# The Bug priority field has a wrong default_value and therefore the field
# value is set on 0 which is not a regular field value.
# Same problem on default_value with the Task percent_complete field.
# But here no negative effect on field values because this field is per 
# default shown on submission
#
# Please apply this patch if you are using MYSQL 4.0.4 or higher. This
# is the case if you are using CodeX verion 2.2 (on branch CX_2_2_SUP
# and on the trunk)
#
#
# References:
# none
#
# Dependencies:
# none
#
#

UPDATE artifact_field 
SET default_value='100' 
WHERE field_name = 'priority' 
AND default_value = '';

UPDATE artifact_field_value afv, artifact_field af, artifact a 
SET afv.valueInt = 100 
WHERE a.artifact_id = afv.artifact_id 
AND af.group_artifact_id = a.group_artifact_id 
AND af.field_id = afv.field_id 
AND af.field_name = 'priority' 
AND afv.valueInt = 0;

UPDATE artifact_field 
SET default_value='1000' 
WHERE field_name = 'percent_complete' 
AND default_value = '';

