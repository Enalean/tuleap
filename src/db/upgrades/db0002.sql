# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0002.sql 406 2002-11-15 15:36:47Z ljulliar $
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
# Refers to task #2313 where Chet Yoder asked for user registration
# approval and we though it would be good if the user could say why 
# s/he wants to register.
#
# Dependencies:
# None
#
# 

ALTER TABLE user ADD register_purpose TEXT AFTER realname;
