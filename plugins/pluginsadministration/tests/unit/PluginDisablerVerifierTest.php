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

namespace Tuleap\PluginsAdministration;

final class PluginDisablerVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @dataProvider providerOptionUntouchablePlugins
     */
    public function testPluginsAdministrationIsAlwaysUntouchable(string|false $plugins_that_can_not_be_disabled): void
    {
        $plugin_administration = $this->createMock(\PluginsAdministrationPlugin::class);
        $plugin_administration->method('getName')->willReturn('pluginsadministration');

        $plugin_disabler_verifier = new PluginDisablerVerifier($plugin_administration, $plugins_that_can_not_be_disabled);

        self::assertFalse($plugin_disabler_verifier->canPluginBeDisabled($plugin_administration));
    }

    public static function providerOptionUntouchablePlugins(): iterable
    {
        return [
            [false],
            [''],
            ['pluginsa'],
            ['pluginsa,pluginsb'],
            [','],
            [',,,'],
        ];
    }

    public function testPluginsCanBeMarkedAsUntouchableWithTheOptionString(): void
    {
        $plugin_administration = $this->createMock(\PluginsAdministrationPlugin::class);
        $plugin_administration->method('getName');

        $plugin_disabler_verifier = new PluginDisablerVerifier($plugin_administration, 'pluginsa, pluginsb');

        $plugin_a = $this->createMock(\PluginsAdministrationPlugin::class);
        $plugin_a->method('getName')->willReturn('pluginsa');
        $plugin_b = $this->createMock(\PluginsAdministrationPlugin::class);
        $plugin_b->method('getName')->willReturn('pluginsb');
        $plugin_c = $this->createMock(\PluginsAdministrationPlugin::class);
        $plugin_c->method('getName')->willReturn('pluginsc');

        self::assertFalse($plugin_disabler_verifier->canPluginBeDisabled($plugin_a));
        self::assertFalse($plugin_disabler_verifier->canPluginBeDisabled($plugin_b));
        self::assertTrue($plugin_disabler_verifier->canPluginBeDisabled($plugin_c));
    }
}
