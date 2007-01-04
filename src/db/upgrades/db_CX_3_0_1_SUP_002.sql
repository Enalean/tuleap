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
#
# Database upgrade script 
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Creates a new table 'forum_monitored_threads' to handle threads monitoring.
# Updates the 'forum_group_list' table, by adding a new forum property : thread_monitored , which
# specifies whether 'thread monitoring' is enabled or not.
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
#
#
# Create 'forum_monitored_threads' table
#

CREATE TABLE forum_monitored_threads (
  thread_monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) NOT NULL default '0',
  thread_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY (thread_monitor_id)
) TYPE=MyISAM;

ALTER TABLE forum_group_list ADD thread_monitored INT(11) NOT NULL default '0';
