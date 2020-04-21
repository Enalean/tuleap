<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use EventManager;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
class GerritCanMigrateCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $can_migrate_checker;
    private $gerrit_server_factory;
    private $project;

    public function setUp(): void
    {
        parent::setUp();

        $this->gerrit_server_factory = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->can_migrate_checker   = new GerritCanMigrateChecker(
            EventManager::instance(),
            $this->gerrit_server_factory
        );

        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        parent::tearDown();
    }

    public function testCanMigrateReturnsFalseIfPlatformCannotUseGerritAndGerritServersNotSet()
    {
        $plugin = $this->buildGerritCanMigrateCheckerTestLDAPFakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_cannot_use_gerrit',
            false
        );

        $gerrit_servers = array();
        $this->gerrit_server_factory->shouldReceive('getAvailableServersForProject')->andReturns($gerrit_servers);

        $this->assertFalse($this->can_migrate_checker->canMigrate($this->project));
    }

    public function testCanMigrateReturnsFalseIfPlatformCannotUseGerritAndGerritServersSet()
    {
        $plugin = $this->buildGerritCanMigrateCheckerTestLDAPFakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_cannot_use_gerrit',
            false
        );

        $gerrit_servers = array(\Mockery::spy(\Git_RemoteServer_GerritServer::class));
        $this->gerrit_server_factory->shouldReceive('getAvailableServersForProject')->andReturns($gerrit_servers);

        $this->assertFalse($this->can_migrate_checker->canMigrate($this->project));
    }

    public function testCanMigrateReturnsFalseIfPlatformCanUseGerritAndGerritServersNotSet()
    {
        $plugin = $this->buildGerritCanMigrateCheckerTestLDAPFakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_can_use_gerrit',
            false
        );

        $gerrit_servers = array();
        $this->gerrit_server_factory->shouldReceive('getAvailableServersForProject')->andReturns($gerrit_servers);

        $this->assertFalse($this->can_migrate_checker->canMigrate($this->project));
    }

    public function testCanMigrateReturnsTrueIfPlatformCanUseGerritAndGerritServersSet()
    {
        $plugin = $this->buildGerritCanMigrateCheckerTestLDAPFakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_can_use_gerrit',
            false
        );

        $gerrit_servers = array('IAmAServer');
        $this->gerrit_server_factory->shouldReceive('getAvailableServersForProject')->andReturns($gerrit_servers);

        $this->assertTrue($this->can_migrate_checker->canMigrate($this->project));
    }

    private function buildGerritCanMigrateCheckerTestLDAPFakePlugin()
    {
        return new class
        {
            //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            public function git_event_platform_cannot_use_gerrit($params)
            {
                $params['platform_can_use_gerrit'] = false;
            }

            //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            public function git_event_platform_can_use_gerrit($params)
            {
                $params['platform_can_use_gerrit'] = true;
            }
        };
    }
}
