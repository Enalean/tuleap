<?php
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

/**
 * This class is responsible of detecting if there are unmet dependencies in plugin
 */
class PluginDependencySolver {

    /** @var PluginManager */
    private $plugin_manager;

    public function __construct(PluginManager $plugin_manager) {
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * Get plugin names that are still installed which depends on the given plugin
     *
     * @return array of strings
     */
    public function getInstalledDependencies(Plugin $plugin) {
        $installed_dependencies = array();
        foreach ($this->plugin_manager->getAllPlugins() as $installed_plugin) {
            if (in_array($plugin->getName(), $installed_plugin->getDependencies())) {
                $installed_dependencies[] = $installed_plugin->getName();
            }
        }
        return $installed_dependencies;
    }

    /**
     * Get plugin names that should already installed for the given plugin name
     *
     * @return array of strings
     */
    public function getUnmetDependencies($plugin_name) {
        $unmet_dependencies = array();
        $plugin = $this->plugin_manager->getTemporaryPlugin($plugin_name);
        foreach ($plugin->getDependencies() as $dependency) {
            if (! $this->plugin_manager->getPluginByName($dependency)) {
                $unmet_dependencies[] = $dependency;
            }
        }
        return $unmet_dependencies;
    }
}
?>
