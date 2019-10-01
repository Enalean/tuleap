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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use MockeryPHPUnitIntegration;

    public function testSettingIsRestrictedByPassDatabaseCall()
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->setIsRestricted(false);
        $plugin->shouldReceive('isAllowed')->never();
        $services = [];
        $params = [
            'project'  => Mockery::mock(Project::class),
            'services' => &$services
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals(['fooservice'], $services);
    }

    public function testNoSettingIsRestrictedFallsbackOnDbCheck()
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->shouldReceive('isAllowed')->once()->andReturn(true);
        $services = [];
        $params = [
            'project'  => Mockery::mock(Project::class, ['getID' => 101]),
            'services' => &$services
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals(['fooservice'], $services);
    }

    public function testSettingIsRestrictedToTrueFallsbackOnDbCheck()
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->setIsRestricted(true);
        $plugin->shouldReceive('isAllowed')->once()->andReturn(true);
        $services = [];
        $params = [
            'project'  => Mockery::mock(Project::class, ['getID' => 101]),
            'services' => &$services
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals(['fooservice'], $services);
    }

    public function testPluginIsNotAllowedToProject()
    {
        $plugin = Mockery::mock(Plugin::class)->makePartial();
        $plugin->shouldReceive('getServiceShortname')->andReturn('fooservice');
        $plugin->shouldReceive('isAllowed')->once()->andReturn(false);
        $services = [];
        $params = [
            'project'  => Mockery::mock(Project::class, ['getID' => 101]),
            'services' => &$services
        ];
        $plugin->services_allowed_for_project($params);

        $this->assertEquals([], $services);
    }
}
