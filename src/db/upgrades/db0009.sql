# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
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
# Add bug_allow_none field in the group table
#
# References:
# Task #1911
#
# Dependencies:
# None
#
# 
alter table groups ADD bug_allow_anon int(11) NOT NULL default '1';

