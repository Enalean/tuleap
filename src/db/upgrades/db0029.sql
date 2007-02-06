# 
# Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
#
# Originally written by Mohamed CHAARI, 2007. STMicroelectronics.
#
# This file is a part of CodeX.
#
# CodeX is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# CodeX is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with CodeX; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
# drop frs_processor table, then re-create it to have processor_id starting from 1,
# insert default values in frs_processor table
# 
# update 
#
# References:
# none
#
# Dependencies:
# none
#
#

## Drop frs_processor table then create it with default values
DROP TABLE frs_processor;

CREATE TABLE frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  rank int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY (processor_id)
) TYPE=MyISAM;

INSERT INTO frs_processor VALUES ('i386','10','100');
INSERT INTO frs_processor VALUES ('PPC','20','100');
INSERT INTO frs_processor VALUES ('MIPS','30','100');
INSERT INTO frs_processor VALUES ('Sparc','40','100');
INSERT INTO frs_processor VALUES ('UltraSparc','50','100');
INSERT INTO frs_processor VALUES ('IA64','60','100');
INSERT INTO frs_processor VALUES ('Alpha','70','100');
INSERT INTO frs_processor VALUES ('Any','80','100');
