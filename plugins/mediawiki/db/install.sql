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