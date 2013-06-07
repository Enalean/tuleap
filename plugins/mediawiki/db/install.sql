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
        1,
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