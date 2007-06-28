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
# Introduce a new 'value_function' field which allows a select box field to take
# its list of values from a programmatic function rather than from a
# manually configured list of values (useful for select box with a list
# of project members for instance)
#
# References:
# Task #2378
#
# Dependencies:
# None
#
#

ALTER TABLE bug_field ADD value_function VARCHAR(255);
ALTER TABLE bug_field_usage ADD custom_value_function VARCHAR(255);

update bug_field set value_function='bug_technicians' where field_name='assigned_to';
update bug_field set value_function='bug_submitters' where field_name='submitted_by';



