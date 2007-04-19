# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id: db0016.sql 817 2004-02-02 16:34:36Z guerin $
#
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# This script will create tables for the snippet categories, language, type and license.
# Those values used to be stored in SF/www/snippet/snippet_utils.php
#
# References:
# Task #3138
#
#

#
# snippet category table
#
CREATE TABLE snippet_category (
  category_id int(11) NOT NULL,
  category_name varchar(255) NOT NULL default ''
);

#
# snippet type table
#
CREATE TABLE snippet_type (
  type_id int(11) NOT NULL,
  type_name varchar(255) NOT NULL default ''
);


#
# snippet license table
#
CREATE TABLE snippet_license (
  license_id int(11) NOT NULL,
  license_name varchar(255) NOT NULL default ''
);


#
# snippet language table
#
CREATE TABLE snippet_language (
  language_id int(11) NOT NULL,
  language_name varchar(255) NOT NULL default ''
);



#
# Populate tables
#

INSERT INTO snippet_category VALUES (100,'None');
INSERT INTO snippet_category VALUES (1,'UNIX Admin');
INSERT INTO snippet_category VALUES (2,'HTML Manipulation');
INSERT INTO snippet_category VALUES (3,'Text Processing');
INSERT INTO snippet_category VALUES (4,'Print Processing');
INSERT INTO snippet_category VALUES (5,'Calendars');
INSERT INTO snippet_category VALUES (6,'Database');
INSERT INTO snippet_category VALUES (7,'Data Structure Manipulation');
INSERT INTO snippet_category VALUES (8,'File Management');
INSERT INTO snippet_category VALUES (9,'Scientific Computation');
INSERT INTO snippet_category VALUES (10,'Office Utilities');
INSERT INTO snippet_category VALUES (11,'User Interface');
INSERT INTO snippet_category VALUES (12,'Other');
INSERT INTO snippet_category VALUES (13,'Network');
INSERT INTO snippet_category VALUES (14,'Data Acquisition and Control');


INSERT INTO snippet_type VALUES (100,'None');
INSERT INTO snippet_type VALUES (1,'Function');
INSERT INTO snippet_type VALUES (2,'Full Script');
INSERT INTO snippet_type VALUES (3,'Sample Code (HOWTO)');
INSERT INTO snippet_type VALUES (4,'README');
INSERT INTO snippet_type VALUES (5,'Class');
INSERT INTO snippet_type VALUES (6,'Full Program');
INSERT INTO snippet_type VALUES (7,'Macros');

INSERT INTO snippet_license VALUES (100,'None');
INSERT INTO snippet_license VALUES (1,'Code eXchange Policy');
INSERT INTO snippet_license VALUES (2,'Other');

INSERT INTO snippet_language VALUES (100,'None');
INSERT INTO snippet_language VALUES (1,'Awk');
INSERT INTO snippet_language VALUES (2,'C');
INSERT INTO snippet_language VALUES (3,'C++');
INSERT INTO snippet_language VALUES (4,'Perl');
INSERT INTO snippet_language VALUES (5,'PHP');
INSERT INTO snippet_language VALUES (6,'Python');
INSERT INTO snippet_language VALUES (7,'Unix Shell');
INSERT INTO snippet_language VALUES (8,'Java');
INSERT INTO snippet_language VALUES (9,'AppleScript');
INSERT INTO snippet_language VALUES (10,'Visual Basic');
INSERT INTO snippet_language VALUES (11,'TCL');
INSERT INTO snippet_language VALUES (12,'Lisp');
INSERT INTO snippet_language VALUES (13,'Mixed');
INSERT INTO snippet_language VALUES (14,'JavaScript');
INSERT INTO snippet_language VALUES (15,'SQL');
INSERT INTO snippet_language VALUES (16,'MatLab');
INSERT INTO snippet_language VALUES (17,'Other Language');
INSERT INTO snippet_language VALUES (18,'LabView');
INSERT INTO snippet_language VALUES (19,'C#');
INSERT INTO snippet_language VALUES (20,'Postscript');

#
# The license set proposed is now more limited than previously
# Codex: was 0, now 1
# Other: was 1, now 2
# all other licenses, now 2
UPDATE snippet SET license=2 where license!=0;
UPDATE snippet SET license=1 where license=0;
