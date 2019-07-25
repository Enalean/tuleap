<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

if ($argc != 3) {
    throw new RuntimeException("Should be run with 2 arguments: username and password");
}

// First: check if LDAP plugin is active
$plugin_manager = PluginManager::instance();
$ldap_plugin    = $plugin_manager->getPluginByName('ldap');
if ($plugin_manager->isPluginAvailable($ldap_plugin)) {
    $user = UserManager::instance()->getUserByUserName($argv[1]);
    if ($user !== null) {
        $user->setPassword($argv[2]);
        $ldap_plugin->user_manager_update_db(array('user' => $user));
    }
}
