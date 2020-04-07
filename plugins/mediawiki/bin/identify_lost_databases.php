<?php
/**
 * Copyright (c) Enalean, 2012-2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * Execute with php launcher:
 *
 * sh /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/plugins/mediawiki/bin/identify_lost_databases.php
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';
$bad_projects_with_mw_sql = "SELECT g.group_id, g.group_name
    FROM groups g, group_plugin gp, plugins p
    WHERE g.group_id = gp.group_id
    AND gp.plugin_id = p.plugin_id
    AND p.plugin_name = 'mediawiki'
    AND g.group_id NOT IN (
        SELECT project_id FROM plugin_mediawiki_database
    )";

$data_access_object = new DataAccessObject();
$data_access_object->enableExceptionsOnError();
try {
    $projects = $data_access_object->retrieve($bad_projects_with_mw_sql);
} catch (DataAccessQueryException $ex) {
    echo $ex->getMessage();
    exit(1);
}

foreach ($projects as $project) {
    $id   = $project['group_id'];
    $name = $project['group_name'];

    echo "Unable to find mediawiki for project $name ($id)" . PHP_EOL;
}
