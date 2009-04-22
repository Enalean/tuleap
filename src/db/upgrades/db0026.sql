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
#   Add a rank field in frs_package table in order give the possibility to
#   order packages.
# 
# SQL script comes next...
#
ALTER TABLE frs_package ADD rank INT( 11 ) DEFAULT '0' NOT NULL ;


