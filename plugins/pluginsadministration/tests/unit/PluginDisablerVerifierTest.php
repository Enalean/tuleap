<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PluginDisablerVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider providerOptionUntouchablePlugins
     */
    public function testPluginsAdministrationIsAlwaysUntouchable($plugins_that_can_not_be_disabled)
    {
        $plugin_administration = \Mockery::mock(\PluginsAdministrationPlugin::class);
        $plugin_administration->shouldReceive('getName')->andReturns('pluginsadministration');

        $plugin_disabler_verifier = new PluginDisablerVerifier($plugin_administration, $plugins_that_can_not_be_disabled);

        $this->assertFalse($plugin_disabler_verifier->canPluginBeDisabled($plugin_administration));
    }

    public function providerOptionUntouchablePlugins()
    {
        return [
            [false],
            [''],
            ['pluginsa'],
            ['pluginsa,pluginsb'],
            [','],
            [',,,']
        ];
    }

    public function testPluginsCanBeMarkedAsUntouchableWithTheOptionString()
    {
        $plugin_administration = \Mockery::mock(\PluginsAdministrationPlugin::class);
        $plugin_administration->shouldReceive('getName');

        $plugin_disabler_verifier = new PluginDisablerVerifier($plugin_administration, 'pluginsa, pluginsb');

        $plugin_a = \Mockery::mock(\PluginsAdministrationPlugin::class);
        $plugin_a->shouldReceive('getName')->andReturn('pluginsa');
        $plugin_b = \Mockery::mock(\PluginsAdministrationPlugin::class);
        $plugin_b->shouldReceive('getName')->andReturn('pluginsb');
        $plugin_c = \Mockery::mock(\PluginsAdministrationPlugin::class);
        $plugin_c->shouldReceive('getName')->andReturn('pluginsc');

        $this->assertFalse($plugin_disabler_verifier->canPluginBeDisabled($plugin_a));
        $this->assertFalse($plugin_disabler_verifier->canPluginBeDisabled($plugin_b));
        $this->assertTrue($plugin_disabler_verifier->canPluginBeDisabled($plugin_c));
    }
}
