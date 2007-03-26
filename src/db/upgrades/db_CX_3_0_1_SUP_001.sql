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
# Add a new table 'artifact_date_reminder_settings' to store settings of trackers date reminder
# Add a new table 'artifact_date_reminder_processing' to store artifacts (data) concerned by reminder mechanism
# Update artifact_field table: add a new field 'notification' to tell whether reminder mechanism is enable for this field (only for date fields) 
#
#
# References:
# no
#
# Dependencies:
# no
#
# 


#
# Add a new table 'artifact_date_reminder_settings'
#
CREATE TABLE artifact_date_reminder_settings (
  reminder_id int(11) NOT NULL auto_increment,
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',  
  notification_start int(11) NOT NULL default '0',
  notification_type int(11) NOT NULL default '0',
  frequency int(11) NOT NULL default '0',
  recurse int(11) NOT NULL default '0',
  notified_people varchar(255) NOT NULL default ''
  ) TYPE=MyISAM;
  
#
# Add a new table 'artifact_date_reminder_processing'
#
CREATE TABLE artifact_date_reminder_processing (
  notification_id int(11) NOT NULL auto_increment,
  reminder_id int(11) NOT NULL default '0',
  artifact_id int(11) NOT NULL default '0',
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  notification_sent int(11) NOT NULL default '0'
  ) TYPE=MyISAM;

#
# Update artifact_field table
#
ALTER TABLE artifact_field ADD notification int(11) default NULL AFTER default_value ;
