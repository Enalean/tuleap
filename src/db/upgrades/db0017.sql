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
# Add new field cvs_preamble in table groups
#
#
# References:
# See task #3139
#
# Dependencies:
# none
#
#

ALTER TABLE groups ADD cvs_preamble text NOT NULL;

