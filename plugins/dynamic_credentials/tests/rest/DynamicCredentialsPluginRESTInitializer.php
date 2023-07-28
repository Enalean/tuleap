<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\REST;

use PluginManager;
use Tuleap\Config\ConfigDao;

final class DynamicCredentialsPluginRESTInitializer
{
    public const PUBLIC_KEY  = 'IpuL6ZHoKzsbGFiFLPuUvD/8dTlZ14t47O5WAyzRpgk=';
    public const PRIVATE_KEY = 'jEaIxuBi/dU3YT/YomtD0Qc/afTSXV4mHVFpuc68EGUim4vpkegrOxsYWIUs+5S8P/x1OVnXi3js7lYDLNGmCQ==';

    public function initialize(): void
    {
        $plugin_manager = PluginManager::instance();
        $plugin_manager->installAndEnable(\dynamic_credentialsPlugin::NAME);
        $plugin = $plugin_manager->getPluginByName(\dynamic_credentialsPlugin::NAME);

        $config_dao = new ConfigDao();
        $config_dao->save('dynamic_credentials_signature_public_key', self::PUBLIC_KEY);
    }
}
