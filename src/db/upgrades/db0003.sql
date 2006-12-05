# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#
# This SQL script allows you to upgrade the CodeX database.
# In most cases this sql script relates to a well indentified
# modification in the CodeX source code.
# All references are included  below.
#
# Description
# Add the register_purpose field to let the user explain why s/he
# wants to register on the site.
#
#
# References:
# Refers to bug #12349. Highly critical bug. release and release_id fields
# were missing from the bug table on the Codex production server and they
# were also missing from db/mysql/database_structure.sql
#
# Dependencies:
# None
#
# 
ALTER TABLE bug ADD release_id INT NOT NULL DEFAULT 100 AFTER keywords;
ALTER TABLE bug ADD release VARCHAR(255) NOT NULL DEFAULT '' AFTER release_id;
