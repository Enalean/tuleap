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
# Change field data in doc_data from longtext to longblob to allow to store binary files.
# Add also file name, type and size.
#
# References:
# Task #254
#
# Dependencies:
# None
#
# 
alter table doc_data change column data data longblob;
alter table doc_data ADD filename text;
alter table doc_data ADD filesize INT UNSIGNED NOT NULL DEFAULT 0;
alter table doc_data ADD filetype text;

update doc_data set filetype='text/html';

