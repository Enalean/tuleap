#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

$list = $argv[1];
$sql = sprintf('SELECT group_id, group_list_id FROM mail_group_list WHERE list_name = "%s"', db_escape_string($list));
$res = db_query($sql);
if (db_numrows($res) > 0) {
    $row = db_fetch_array($res);
    $list_id    = $row['group_list_id'];
    $project_id = $row['group_id'];
} else {
    fwrite(STDERR, "Invalid mailing-list $list\n");
    exit(1);
}

$plugin_manager = PluginManager::instance();
$plugin = $plugin_manager->getPluginByName('forumml');
if ($plugin && $plugin_manager->isPluginAvailable($plugin) && $plugin_manager->isPluginAllowedForProject($plugin, $project_id)) {
    $info = $plugin->getPluginInfo();

    // Get ForumML storage
    $forumml_dir     = $info->getPropertyValueForName('forumml_dir');
    $forumml_storage = new ForumML_FileStorage($forumml_dir);

    // Store email
    $incoming_mail = new \Tuleap\ForumML\Incoming\IncomingMail(STDIN);
    $archiver      = new \Tuleap\ForumML\MessageArchiver($list_id);
    $archiver->storeEmail($incoming_mail, $forumml_storage);
}
