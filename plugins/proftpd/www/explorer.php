<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

// hack to make sure that pseudo-nice urls don't bypass the restricted user check
if (preg_match_all('/^\/plugins\/proftpd\/index.php\/(\d+)\/([^\/][a-zA-Z]+)\/([a-zA-Z\-\_0-9]+)\/\?{0,1}.*/', $_SERVER['REQUEST_URI'], $matches)) {
    $_REQUEST['group_id'] = $_GET['group_id'] = $matches[1][0];
}

$plugin_manager = PluginManager::instance();
$p = $plugin_manager->getPluginByName('proftpd');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $request = new HTTPRequest(['controller' => 'explorer']);
    $p->process($request);
} else {
    header('Location: /');
}
