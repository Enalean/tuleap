/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

CREATE TABLE IF NOT EXISTS plugin_phpwiki_page (
	id              INT NOT NULL AUTO_INCREMENT,
    pagename        VARCHAR(100) BINARY NOT NULL,
	hits            INT NOT NULL DEFAULT 0,
    pagedata        MEDIUMTEXT NOT NULL DEFAULT '',
	cached_html 	MEDIUMBLOB,
	group_id        INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_page_group (group_id,pagename(10))
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_version (
	id              INT NOT NULL,
        version         INT NOT NULL,
	mtime           INT NOT NULL,
	minor_edit      TINYINT DEFAULT 0,
        content         MEDIUMTEXT NOT NULL DEFAULT '',
        versiondata     MEDIUMTEXT NOT NULL DEFAULT '',
        PRIMARY KEY (id,version),
	INDEX (mtime)
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_recent (
	id              INT NOT NULL,
	latestversion   INT,
	latestmajor     INT,
	latestminor     INT,
        PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_nonempty (
	id              INT NOT NULL,
	PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_link (
	linkfrom        INT NOT NULL,
        linkto          INT NOT NULL,
        relation        INT DEFAULT 0,
	INDEX (linkfrom),
        INDEX (linkto),
        INDEX (relation)
);

#
# Wiki Service
#

CREATE TABLE IF NOT EXISTS plugin_phpwiki_group_list (
	id int(11) NOT NULL auto_increment,
	group_id int(11) NOT NULL default '0',
	wiki_name varchar(255) NOT NULL default '',
	wiki_link varchar(255) NOT NULL default '',
	description varchar(255) NOT NULL default '',
	rank int(11) NOT NULL default '0',
        language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US',
	PRIMARY KEY (id)
);

# Table for Wiki access logs
CREATE TABLE IF NOT EXISTS plugin_phpwiki_log (
        user_id int(11) NOT NULL default '0',
        group_id int(11) NOT NULL default '0',
        pagename varchar(255) NOT NULL default '',
        time int(11) NOT NULL default '0',
        KEY all_idx (user_id,group_id),
        KEY time_idx (time),
        KEY group_id_idx (group_id)
);


# Tables for Wiki attachments support
CREATE TABLE IF NOT EXISTS plugin_phpwiki_attachment (
        id INT( 11 ) NOT NULL AUTO_INCREMENT ,
        group_id INT( 11 ) NOT NULL ,
        name VARCHAR( 255 ) NOT NULL ,
        filesystem_name VARCHAR( 255 ) DEFAULT NULL,
        delete_date INT(11) UNSIGNED NULL,
        PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_attachment_deleted (
        id INT( 11 ) NOT NULL AUTO_INCREMENT ,
        group_id INT( 11 ) NOT NULL ,
        name VARCHAR( 255 ) NOT NULL ,
        filesystem_name VARCHAR( 255 ) DEFAULT NULL,
        delete_date INT(11) UNSIGNED NULL,
        purge_date INT(11) UNSIGNED NULL,
        PRIMARY KEY (id),
        INDEX idx_delete_date (delete_date),
        INDEX idx_purge_date (purge_date)
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_attachment_revision (
        id INT( 11 ) NOT NULL AUTO_INCREMENT ,
        attachment_id INT( 11 ) NOT NULL ,
        user_id INT( 11 ) NOT NULL ,
        date INT( 11 ) NOT NULL ,
        revision INT( 11 ) NOT NULL ,
        mimetype VARCHAR( 255 ) NOT NULL ,
        size bigint NOT NULL ,
        PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS plugin_phpwiki_attachment_log (
        user_id int(11) NOT NULL default '0',
        group_id int(11) NOT NULL default '0',
        wiki_attachment_id int(11) NOT NULL default '0',
        wiki_attachment_revision_id int(11) NOT NULL default '0',
        time int(11) NOT NULL default '0',
        KEY all_idx (user_id,group_id),
        KEY time_idx (time),
        KEY group_id_idx (group_id)
);

-- Enable service for project 100
INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank)
    VALUES (100, 'plugin_phpwiki:service_lbl_key', 'plugin_phpwiki:service_desc_key', 'plugin_phpwiki', '/plugins/phpwiki/?group_id=$group_id', 1, 0, 'system', 106);

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
  SELECT DISTINCT group_id, 'plugin_phpwiki:service_lbl_key', 'plugin_phpwiki:service_desc_key', 'plugin_phpwiki', CONCAT('/plugins/phpwiki/?group_id=', group_id), 1, 0, 'system', 106
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'plugin_phpwiki');

-- References
INSERT INTO reference SET
        keyword='phpwiki',
        description='plugin_phpwiki:reference_phpwiki_desc_key',
        link='/plugins/phpwiki/?group_id=$group_id&pagename=$1',
        scope='S',
        service_short_name='PHPWiki',
        nature='wiki_page';
INSERT INTO reference_group (reference_id, group_id, is_active)
        SELECT last_insert_id, group_id, 1
        FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups;
INSERT INTO reference SET
        keyword='phpwiki',
        description='plugin_phpwiki:reference_phpwikiversion_desc_key',
        link='/plugins/phpwiki/?group_id=$group_id&pagename=$1&version=$2',
        scope='S',
        service_short_name='PHPWiki',
        nature='wiki_page';
INSERT INTO reference_group (reference_id, group_id, is_active)
        SELECT last_insert_id, group_id, 1
        FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups;

-- Permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKI_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKI_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PHPWIKI_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKI_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKI_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKI_READ',14);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIPAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIPAGE_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PHPWIKIPAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIPAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIPAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIPAGE_READ',14);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIATTACHMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIATTACHMENT_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PHPWIKIATTACHMENT_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIATTACHMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PHPWIKIATTACHMENT_READ',4);