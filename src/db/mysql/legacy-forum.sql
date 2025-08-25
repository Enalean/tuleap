/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

#

CREATE TABLE forum (
                       msg_id int(11) NOT NULL auto_increment,
                       group_forum_id int(11) NOT NULL default '0',
                       posted_by int(11) NOT NULL default '0',
                       subject text NOT NULL,
                       body text NOT NULL,
                       date int(11) NOT NULL default '0',
                       is_followup_to int(11) NOT NULL default '0',
                       thread_id int(11) NOT NULL default '0',
                       has_followups int(11) default '0',
                       PRIMARY KEY  (msg_id),
                       KEY idx_forum_group_forum_id (group_forum_id),
                       KEY idx_forum_is_followup_to (is_followup_to),
                       KEY idx_forum_thread_id (thread_id),
                       KEY idx_forum_id_date (group_forum_id,date),
                       KEY idx_forum_id_date_followup (group_forum_id,date,is_followup_to),
                       KEY idx_forum_thread_date_followup (thread_id,date,is_followup_to)
);

#
# Table structure for table 'forum_group_list'
#

CREATE TABLE forum_group_list (
                                  group_forum_id int(11) NOT NULL auto_increment,
                                  group_id int(11) NOT NULL default '0',
                                  forum_name text NOT NULL,
                                  is_public int(11) NOT NULL default '0',
                                  description text,
                                  PRIMARY KEY  (group_forum_id),
                                  FULLTEXT (description),
                                  KEY idx_forum_group_list_group_id (group_id)
);

#
# Table structure for table 'forum_monitored_forums'
#

CREATE TABLE forum_monitored_forums (
                                        monitor_id int(11) NOT NULL auto_increment,
                                        forum_id int(11) NOT NULL default '0',
                                        user_id int(11) NOT NULL default '0',
                                        PRIMARY KEY  (monitor_id),
                                        KEY idx_forum_monitor_thread_id (forum_id),
                                        KEY idx_forum_monitor_combo_id (forum_id,user_id)
);

#
# Table structure for 'forum_monitored_threads' table
#

CREATE TABLE forum_monitored_threads (
                                         thread_monitor_id int(11) NOT NULL auto_increment,
                                         forum_id int(11) NOT NULL default '0',
                                         thread_id int(11) NOT NULL default '0',
                                         user_id int(11) NOT NULL default '0',
                                         PRIMARY KEY (thread_monitor_id)
);

#
# Table structure for table 'forum_saved_place'
#

CREATE TABLE forum_saved_place (
                                   saved_place_id int(11) NOT NULL auto_increment,
                                   user_id int(11) NOT NULL default '0',
                                   forum_id int(11) NOT NULL default '0',
                                   save_date int(11) NOT NULL default '0',
                                   PRIMARY KEY  (saved_place_id)
);

#
# Table structure for table 'forum_thread_id'
#

CREATE TABLE forum_thread_id (
                                 thread_id int(11) NOT NULL auto_increment,
                                 PRIMARY KEY  (thread_id)
);


#
# Default Data for 'user_group'
#
# Make the 'admin' user part of the default Admin Project so that he
# becomes a super user
# flags after 'A' are: bug,forum,project,patch,support,file,wiki,svn,news

INSERT INTO user_group VALUES (1, 101, 1, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2);

insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (4, 100, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=$group_id', 0, 0, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (34, 1, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=1', 0, 0, 'system', 40);

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (16, "ugroup_forum_admin_name_key", "ugroup_forum_admin_desc_key", 100);

INSERT INTO reference SET \
      id='14',        \
      keyword='msg', \
      description='reference_msg_desc_key', \
      link='/forum/message.php?msg_id=$1', \
      scope='S', \
      service_short_name='forum', \
      nature='forum_message';

--
-- Add forums in Template project (group 100)
--
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Open Discussion',1 ,'General Discussion');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Help',1 ,'Get Help');
INSERT INTO forum_group_list (group_id,forum_name,is_public,description) VALUES ('100','Developers',0 ,'Project Developer Discussion');
