<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

require_once 'pre.php';

activatePlugin($argv[1]);

function activatePlugin($name) {
    $plugin_factory = PluginFactory::instance();
    $plugin = $plugin_factory->getPluginByName($name);
    if (! $plugin) {
        echo "Install plugin\n";
        $plugin_manager = new PluginManager();
        $plugin = $plugin_manager->installPlugin($name);
    }
    if (! $plugin_factory->isPluginAvailable($plugin)) {
        echo "Activate plugin\n";
        $plugin_factory->availablePlugin($plugin);
    }
}
