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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitPluginPostSystemEvents extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin               = \Mockery::mock(\GitPlugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->gitolite_driver      = \Mockery::spy(\Git_GitoliteDriver::class);

        $this->plugin->shouldReceive('getGitSystemEventManager')->andReturns($this->system_event_manager);
        $this->plugin->shouldReceive('getGitoliteDriver')->andReturns($this->gitolite_driver);
        $this->plugin->shouldReceive('getLogger')->andReturns(\Mockery::spy(\TruncateLevel\Psr\Log\LoggerInterface::class));
    }

    public function testItProcessGrokmirrorManifestUpdateInPostSystemEventsActions(): void
    {
        $this->gitolite_driver->shouldReceive('commit')
            ->once();

        $this->gitolite_driver->shouldReceive('push')
            ->once();

        $params = array(
            'executed_events_ids' => array(125),
            'queue_name' => 'git'
        );

        $this->plugin->post_system_events_actions($params);
    }

    public function testItDoesNotProcessPostSystemEventsActionsIfNotGitRelated(): void
    {
        $this->gitolite_driver->shouldReceive('commit')
            ->never();

        $this->gitolite_driver->shouldReceive('push')
            ->never();

        $params = array(
            'executed_events_ids' => array(54156),
            'queue_name'          => 'owner'
        );

        $this->plugin->post_system_events_actions($params);
    }
}
