# Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
#
# Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
#
# This file is a part of CodeX.
# CodeX is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# CodeX is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with CodeX; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# 1- create a new table 'survey_radio_choices' to the survey manager database. This table contains all useful
# information about edited radio buttons, it has 4 columns : 'choice_id', 'question_id', 'radio_choice' and
# 'choice_rank'
# 2- define a new question type 'Radio Buttons' in 'survey_question_types' table
# 3- change type name of yes/no questions from 'Radio Button Yes/No' to 'Yes/No'
# 
# Update 
#
# References:
# request #391
#
# Dependencies:
# none
#
#

## Create the new table 'survey_radio_choices'
CREATE TABLE survey_radio_choices (
  choice_id int(11) NOT NULL auto_increment,
  question_id int(11) NOT NULL,  
  choice_rank int(11) NOT NULL,
  radio_choice text NOT NULL,
  PRIMARY KEY  (choice_id)  
) TYPE=MyISAM;

## Add new type value 'Radio Buttons', id=6, in 'survey_question_types' table
INSERT INTO survey_question_types (id, type) VALUES (6,'Radio Buttons');

## Replace 'Radio Button Yes/No' value by 'Yes/No' in 'survey_question_types' table
UPDATE survey_question_types SET type='Yes/No' WHERE id='3';
