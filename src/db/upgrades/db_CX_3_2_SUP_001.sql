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
# Creates a new event 'COMMENT_CHANGE' in artifact notification tables.
# Transfer the existing followup comments (in prod) from 'old_value' field to 'new_value'.
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

INSERT INTO artifact_notification_event_default (event_id,event_label,rank,short_description_msg,description_msg) VALUES (10,"COMMENT_CHANGE",100,"event_COMMENT_CHANGE_short_desc","event_COMMENT_CHANGE_desc");

INSERT INTO artifact_notification_event (event_id,group_artifact_id,event_label,rank,short_description_msg,description_msg) SELECT 10,group_artifact_id,"COMMENT_CHANGE",100,"event_COMMENT_CHANGE_short_desc","event_COMMENT_CHANGE_desc" FROM artifact_group_list WHERE group_artifact_id > 100;

UPDATE artifact_history SET new_value = old_value , old_value = "" WHERE field_name = "comment";
