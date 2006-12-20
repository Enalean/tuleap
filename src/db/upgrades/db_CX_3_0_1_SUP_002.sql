# 
# Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
#
# Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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
# Add new fields in frs_processor table : group_id and rank
# change processor_id values for Codex predefined processors
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

ALTER TABLE frs_processor ADD group_id int(11);

ALTER TABLE frs_processor ADD rank int(11);

UPDATE frs_processor SET group_id = 100;

UPDATE frs_processor SET rank = 10, processor_id = 1 WHERE name = "i386";
UPDATE frs_processor SET rank = 20, processor_id = 2 WHERE name = "PPC";
UPDATE frs_processor SET rank = 30, processor_id = 3 WHERE name = "MIPS";
UPDATE frs_processor SET rank = 40, processor_id = 4 WHERE name = "Sparc";
UPDATE frs_processor SET rank = 50, processor_id = 5 WHERE name = "UltraSparc";
UPDATE frs_processor SET rank = 60, processor_id = 6 WHERE name = "IA64";
UPDATE frs_processor SET rank = 70, processor_id = 7 WHERE name = "Alpha";
UPDATE frs_processor SET rank = 80, processor_id = 8 WHERE name = "Any";
UPDATE frs_processor SET rank = 90, processor_id = 9 WHERE name = "Other"