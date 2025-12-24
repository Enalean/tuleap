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
# Table structure for table 'news_bytes'
#

CREATE TABLE news_bytes (
                            id int(11) NOT NULL auto_increment,
                            group_id int(11) NOT NULL default '0',
                            submitted_by int(11) NOT NULL default '0',
                            is_approved int(11) NOT NULL default '0',
                            date int(11) NOT NULL default '0',
                            forum_id int(11) NOT NULL default '0',
                            summary text,
                            details text,
                            PRIMARY KEY  (id),
                            KEY idx_news_bytes_forum (forum_id),
                            KEY idx_news_bytes_group (group_id),
                            KEY idx_news_bytes_approved (is_approved)
);


INSERT INTO `groups` SET \
                             group_id = '46', \
                             group_name = 'Site News', \
                             access = 'private', \
                             status = 'A', \
                             unix_group_name = 'sitenews', \
                             short_description = 'Site News Private Project. All Site News should be posted from this project', \
                             svn_box = 'svn1', \
                             register_time = 940000000, \
                             rand_hash = '', \
                             type = '1', \
                             built_from_template = '100', \
                             svn_tracker = '0', \
                             svn_mandatory_ref = '0', \
                             svn_events_mailing_header = '', \
                             svn_preamble = '', \
                             svn_commit_to_tag_denied ='0' ;


insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (12, 100, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=$group_id', 0, 0, 'system', 120);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (38, 1, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=1', 0, 0, 'system', 120);

-- Group 46 - SiteNews
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (51, 46, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/sitenews/', 1, 1, 'system', 10);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (52, 46, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (54, 46, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=46', 1, 1, 'system', 40);
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES (55, 46, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=46', 1, 1, 'system', 120);

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (17, "ugroup_news_admin_name_key", "ugroup_news_admin_desc_key", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (18, "ugroup_news_writer_name_key", "ugroup_news_writer_desc_key", 100);

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('NEWS_READ',1,1);


INSERT INTO reference SET \
                              id='12',        \
                              keyword='news', \
                              description='reference_news_desc_key', \
                              link='/forum/forum.php?forum_id=$1', \
                              scope='S', \
                              service_short_name='news', \
                              nature='news';

-- Sitenews project (group 46)
INSERT INTO reference_group SET reference_id='12', group_id='46', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='46', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='46', is_active='1';
