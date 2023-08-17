<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

function forge_get_config($key, $scope = 'core')
{
    $conf_variables_mapping = [
        'web_host'          => 'sys_default_domain',
        'forge_name'        => 'sys_name',
        //'config_path'       => 'sys_custom_dir',
        'database_host'     => 'sys_dbhost',
        'database_user'     => 'sys_dbuser',
        'database_name'     => 'sys_dbname',
        'database_password' => 'sys_dbpasswd',
    ];
    if (isset($conf_variables_mapping[$key])) {
        $key = $conf_variables_mapping[$key];
    } elseif ($scope !== 'core') {
        $plugin_manager = PluginManager::instance();
        $plugin         = $plugin_manager->getPluginByName($scope);
        if (! $plugin || ! $plugin_manager->isPluginEnabled($plugin)) {
            return null;
        }
        return $plugin->getPluginInfo()->getPropertyValueForName($key);
    }
    return ForgeConfig::get($key);
}
