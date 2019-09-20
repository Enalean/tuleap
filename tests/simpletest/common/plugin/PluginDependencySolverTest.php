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

class PluginDependencySolverTest extends TuleapTestCase
{

    public function setUp()
    {
        $this->tracker_plugin            = stub('Plugin')->getName()->returns('tracker');
        $this->mediawiki_plugin          = stub('Plugin')->getName()->returns('mediawiki');
        $this->fusionforge_compat_plugin = stub('Plugin')->getName()->returns('fusionforge_compat');

        stub($this->tracker_plugin)->getDependencies()->returns(array());
        stub($this->mediawiki_plugin)->getDependencies()->returns(array('fusionforge_compat'));
        stub($this->fusionforge_compat_plugin)->getDependencies()->returns(array());

        $this->plugin_manager = mock('PluginManager');
        stub($this->plugin_manager)->getPluginDuringInstall('tracker')->returns($this->tracker_plugin);
        stub($this->plugin_manager)->getPluginDuringInstall('mediawiki')->returns($this->mediawiki_plugin);
        stub($this->plugin_manager)->getPluginDuringInstall('fusionforge_compat')->returns($this->fusionforge_compat_plugin);
    }

    public function itReturnsTheInstalledDependencies()
    {
        $installed_plugin = array($this->mediawiki_plugin, $this->tracker_plugin, $this->fusionforge_compat_plugin);
        stub($this->plugin_manager)->getAllPlugins()->returns($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        $this->assertEqual(
            $solver->getInstalledDependencies($this->fusionforge_compat_plugin),
            array('mediawiki')
        );
    }

    public function itReturnsTheUnmetDependencies()
    {
        $installed_plugin = array($this->tracker_plugin);
        stub($this->plugin_manager)->getPluginByName('tracker')->returns($this->tracker_plugin);
        stub($this->plugin_manager)->getAllPlugins()->returns($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        $this->assertEqual(
            $solver->getUnmetInstalledDependencies('mediawiki'),
            array('fusionforge_compat')
        );
    }

    public function itReturnsEmptyArrayWhenDependenciesAreMet()
    {
        $installed_plugin = array($this->tracker_plugin, $this->fusionforge_compat_plugin);
        stub($this->plugin_manager)->getPluginByName('tracker')->returns($this->tracker_plugin);
        stub($this->plugin_manager)->getPluginByName('fusionforge_compat')->returns($this->fusionforge_compat_plugin);
        stub($this->plugin_manager)->getAllPlugins()->returns($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        $this->assertEqual(
            $solver->getUnmetInstalledDependencies('mediawiki'),
            array()
        );
    }
}
