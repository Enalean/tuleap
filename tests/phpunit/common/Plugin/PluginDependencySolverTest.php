<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class PluginDependencySolverTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Plugin
     */
    private $tracker_plugin;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Plugin
     */
    private $mediawiki_plugin;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Plugin
     */
    private $fusionforge_compat_plugin;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PluginManager
     */
    private $plugin_manager;

    protected function setUp(): void
    {
        $this->tracker_plugin            = \Mockery::spy(Plugin::class)->shouldReceive('getName')->andReturns('tracker')->getMock();
        $this->mediawiki_plugin          = \Mockery::spy(Plugin::class)->shouldReceive('getName')->andReturns('mediawiki')->getMock();
        $this->fusionforge_compat_plugin = \Mockery::spy(Plugin::class)->shouldReceive('getName')->andReturns('fusionforge_compat')->getMock();

        $this->tracker_plugin->shouldReceive('getDependencies')->andReturns([]);
        $this->mediawiki_plugin->shouldReceive('getDependencies')->andReturns(['fusionforge_compat']);
        $this->fusionforge_compat_plugin->shouldReceive('getDependencies')->andReturns([]);

        $this->plugin_manager = \Mockery::spy(\PluginManager::class);
        $this->plugin_manager->shouldReceive('getPluginDuringInstall')->with('tracker')->andReturns($this->tracker_plugin);
        $this->plugin_manager->shouldReceive('getPluginDuringInstall')->with('mediawiki')->andReturns($this->mediawiki_plugin);
        $this->plugin_manager->shouldReceive('getPluginDuringInstall')->with('fusionforge_compat')->andReturns($this->fusionforge_compat_plugin);
    }

    public function testItReturnsTheInstalledDependencies(): void
    {
        $installed_plugin = array($this->mediawiki_plugin, $this->tracker_plugin, $this->fusionforge_compat_plugin);
        $this->plugin_manager->shouldReceive('getAllPlugins')->andReturns($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        $this->assertEquals(
            ['mediawiki'],
            $solver->getInstalledDependencies($this->fusionforge_compat_plugin),
        );
    }

    public function testItReturnsTheUnmetDependencies(): void
    {
        $installed_plugin = array($this->tracker_plugin);
        $this->plugin_manager->shouldReceive('getPluginByName')->with('tracker')->andReturns($this->tracker_plugin);
        $this->plugin_manager->shouldReceive('getAllPlugins')->andReturns($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        $this->assertEquals(
            ['fusionforge_compat'],
            $solver->getUnmetInstalledDependencies('mediawiki')
        );
    }

    public function testItReturnsEmptyArrayWhenDependenciesAreMet(): void
    {
        $installed_plugin = array($this->tracker_plugin, $this->fusionforge_compat_plugin);
        $this->plugin_manager->shouldReceive('getPluginByName')->with('tracker')->andReturns($this->tracker_plugin);
        $this->plugin_manager->shouldReceive('getPluginByName')->with('fusionforge_compat')->andReturns($this->fusionforge_compat_plugin);
        $this->plugin_manager->shouldReceive('getAllPlugins')->andReturns($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        $this->assertEquals(
            [],
            $solver->getUnmetInstalledDependencies('mediawiki')
        );
    }
}
