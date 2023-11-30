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

declare(strict_types=1);

namespace Tuleap\Plugin;

use PHPUnit\Framework\MockObject\MockObject;
use Plugin;
use PluginDependencySolver;

final class PluginDependencySolverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Plugin&MockObject $tracker_plugin;
    private Plugin&MockObject $mediawiki_plugin;
    private Plugin&MockObject $fusionforge_compat_plugin;
    private \PluginManager&MockObject $plugin_manager;

    protected function setUp(): void
    {
        $this->tracker_plugin = $this->createMock(Plugin::class);
        $this->tracker_plugin->method('getName')->willReturn('tracker');
        $this->mediawiki_plugin = $this->createMock(Plugin::class);
        $this->mediawiki_plugin->method('getName')->willReturn('mediawiki');
        $this->fusionforge_compat_plugin = $this->createMock(Plugin::class);
        $this->fusionforge_compat_plugin->method('getName')->willReturn('fusionforge_compat');

        $this->tracker_plugin->method('getDependencies')->willReturn([]);
        $this->mediawiki_plugin->method('getDependencies')->willReturn(['fusionforge_compat']);
        $this->fusionforge_compat_plugin->method('getDependencies')->willReturn([]);

        $this->plugin_manager = $this->createMock(\PluginManager::class);
    }

    public function testItReturnsTheInstalledDependencies(): void
    {
        $installed_plugin = [$this->mediawiki_plugin, $this->tracker_plugin, $this->fusionforge_compat_plugin];
        $this->plugin_manager->method('getAllPlugins')->willReturn($installed_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        self::assertEquals(
            ['mediawiki'],
            $solver->getInstalledDependencies($this->fusionforge_compat_plugin),
        );
    }

    public function testItReturnsTheUnmetDependencies(): void
    {
        $installed_plugin = [$this->tracker_plugin];
        $this->plugin_manager->method('getPluginByName')->with('fusionforge_compat');
        $this->plugin_manager->method('getAllPlugins')->willReturn($installed_plugin);
        $this->plugin_manager->method('getPluginDuringInstall')->with('mediawiki')->willReturn($this->mediawiki_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        self::assertEquals(
            ['fusionforge_compat'],
            $solver->getUninstalledDependencies('mediawiki')
        );
    }

    public function testItReturnsEmptyArrayWhenDependenciesAreMet(): void
    {
        $installed_plugin = [$this->tracker_plugin, $this->fusionforge_compat_plugin];
        $this->plugin_manager->method('getPluginByName')->withConsecutive(
            ['fusionforge_compat'],
            ['tracker']
        )->willReturnOnConsecutiveCalls(
            $this->fusionforge_compat_plugin,
            $this->tracker_plugin
        );
        $this->plugin_manager->method('getAllPlugins')->willReturn($installed_plugin);
        $this->plugin_manager->method('getPluginDuringInstall')->with('mediawiki')->willReturn($this->mediawiki_plugin);
        $solver = new PluginDependencySolver($this->plugin_manager);

        self::assertEquals(
            [],
            $solver->getUninstalledDependencies('mediawiki')
        );
    }
}
