<?php
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

require_once 'pre.php';

try {
    ini_set('html_errors', 'off');

    $plugin_manager = PluginManager::instance();
    $plugin = $plugin_manager->getPluginByName('git');
    if (! $plugin || ! $plugin_manager->isPluginAvailable($plugin)) {
        die('Git not enabled');
    }

    $logger = new WrapperLogger($plugin->getLogger(), 'http');

    $logger->debug($_SERVER['PATH_INFO']);

    $command_factory = new Git_HTTP_CommandFactory(
        $plugin->getRepositoryFactory(),
        new User_LoginManager(
            EventManager::instance(),
            UserManager::instance(),
            new User_PasswordExpirationChecker()
        ),
        PermissionsManager::instance(),
        $logger
    );

    $http_wrapper = new Git_HTTP_Wrapper();
    $http_wrapper->stream($command_factory->getCommand());

} catch (GitRepoNotFoundException $exception) {
    header('HTTP/1.0 404 Not found');
}
