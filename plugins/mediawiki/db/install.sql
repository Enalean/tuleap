/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

-- copied from mediawiki maintenance/tables.sql
CREATE TABLE plugin_mediawiki_interwiki (
  -- The interwiki prefix, (e.g. "Meatball", or the language prefix "de")
  iw_prefix varchar(32) NOT NULL,

  -- The URL of the wiki, with "$1" as a placeholder for an article name.
  -- Any spaces in the name will be transformed to underscores before
  -- insertion.
  iw_url blob NOT NULL,

  -- The URL of the file api.php
  iw_api blob NOT NULL,

  -- The name of the database (for a connection to be established with wfGetLB( 'wikiid' ))
  iw_wikiid varchar(64) NOT NULL,

  -- A boolean value indicating whether the wiki is in this project
  -- (used, for example, to detect redirect loops)
  iw_local bool NOT NULL,

  -- Boolean value indicating whether interwiki transclusions are allowed.
  iw_trans tinyint NOT NULL default 0
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/iw_prefix ON plugin_mediawiki_interwiki (iw_prefix);

CREATE VIEW group_plugin
    AS (
        SELECT service.service_id group_plugin_id,
            service.group_id,
            plugin.id plugin_id,
            service.short_name
        FROM service,plugin
        WHERE service.short_name = CONCAT('plugin_', plugin.name)
            AND service.is_active=1
            AND service.is_used=1
            AND service.group_id != 100
        );

CREATE VIEW plugins
    AS (
        SELECT id plugin_id,
            name plugin_name,
            name plugin_desc
        FROM plugin
        );

INSERT
    INTO service(
            `group_id`,
            `label`,
            `description`,
            `short_name`,
            `link`,
            `is_active`,
            `is_used`,
            `scope`,
            `rank`
        )
    VALUES(
        100,
        'plugin_mediawiki:service_lbl_key',
        'plugin_mediawiki:service_desc_key',
        'plugin_mediawiki',
        '/plugins/mediawiki/wiki/$projectname',
        1,
        0,
        'system',
        160
    );
INSERT
    INTO service(
        `group_id`,
        `label`,
        `description`,
        `short_name`,
        `link`,
        `is_active`,
        `is_used`,
        `scope`,
        `rank`
        )
    SELECT DISTINCT
        service.group_id ,
        'plugin_mediawiki:service_lbl_key' ,
        'plugin_mediawiki:service_desc_key' ,
        'plugin_mediawiki',
        CONCAT('/plugins/mediawiki/wiki/',
        LOWER(groups.unix_group_name)),
        1 ,
        0 ,
        'system',
        160
    FROM service
    JOIN groups
        ON (groups.group_id = service.group_id)
    WHERE service.group_id != 100;

CREATE TABLE IF NOT EXISTS plugin_mediawiki_ugroup_mapping (
    group_id  INT(11) UNSIGNED NOT NULL,
    ugroup_id INT(11) NOT NULL,
    mw_group_name ENUM( 'anonymous', 'user', 'bot', 'sysop', 'bureaucrat' ) NOT NULL DEFAULT 'anonymous'
);

INSERT INTO plugin_mediawiki_ugroup_mapping(group_id, ugroup_id, mw_group_name)
VALUES
    (100, 4, 'sysop'),
    (100, 4, 'bureaucrat');

DROP TABLE IF EXISTS plugin_mediawiki_tuleap_mwgroups;
CREATE TABLE plugin_mediawiki_tuleap_mwgroups (
    mw_group_name ENUM( 'anonymous', 'user', 'bot', 'sysop', 'bureaucrat' ) NOT NULL DEFAULT 'anonymous',
    real_name varbinary(32) NOT NULL DEFAULT '',
    INDEX idx_mw_group_name (mw_group_name)
);

INSERT INTO plugin_mediawiki_tuleap_mwgroups(mw_group_name, real_name)
VALUES
    ('anonymous', '*'),
    ('user', 'user'),
    ('bot', 'bot'),
    ('sysop', 'sysop'),
    ('bureaucrat', 'bureaucrat');

CREATE TABLE IF NOT EXISTS plugin_mediawiki_database (
    project_id INT(11) UNSIGNED NOT NULL,
    database_name VARCHAR(255) NULL,
    PRIMARY KEY (project_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_mediawiki_admin_options (
    project_id INT(11) UNSIGNED PRIMARY KEY,
    enable_compatibility_view BOOLEAN DEFAULT 0,
    language VARCHAR(7) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_mediawiki_site_restricted_features (
    feature INT NOT NULL,
    project_id int(11) NOT NULL,
    PRIMARY KEY (feature, project_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_mediawiki_access_control (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    project_id INT(11) NOT NULL,
    access VARCHAR(30),
    ugroup_id INT(11) NOT NULL,
    INDEX plugin_mediawiki_access_control_idx(project_id, access)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_mediawiki_version (
    project_id INT(11) UNSIGNED PRIMARY KEY,
    mw_version VARCHAR(10),
    INDEX idx_version (mw_version(4))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_mediawiki_extension (
  project_id INT(11) UNSIGNED PRIMARY KEY,
  extension_mleb TINYINT(1) NOT NULL DEFAULT 0,
  extension_math TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;