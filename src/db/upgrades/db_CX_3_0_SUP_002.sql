# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
# Database upgrade script 
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Add a new type of questions in survey manager : sleect-box. Add a new entry in survey_question_types table.
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

INSERT INTO survey_question_types (id, type, rank) VALUES (7,'select_box', '23');