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

namespace Tuleap\Git\RemoteServer;

use Tuleap\Git\Git\RemoteServer\GerritCanMigrateEvent;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

require_once __DIR__ . '/../../bootstrap.php';

class GerritCanMigrateCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Project $project;
    private \Git_RemoteServer_GerritServerFactory&\PHPUnit\Framework\MockObject\MockObject $gerrit_server_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->gerrit_server_factory = $this->createMock(\Git_RemoteServer_GerritServerFactory::class);
        $this->project               = ProjectTestBuilder::aProject()->withId(101)->withAccessPublic()->build();
    }

    public function testCanMigrateReturnsFalseIfPlatformCannotUseGerritAndGerritServersNotSet()
    {
        $checker = new GerritCanMigrateChecker(
            EventDispatcherStub::withIdentityCallback(),
            $this->gerrit_server_factory
        );

        $gerrit_servers = [];
        $this->gerrit_server_factory
            ->method('getAvailableServersForProject')
            ->willReturn($gerrit_servers);

        $this->assertFalse($checker->canMigrate($this->project));
    }

    public function testCanMigrateReturnsFalseIfPlatformCannotUseGerritAndGerritServersSet()
    {
        $checker = new GerritCanMigrateChecker(
            EventDispatcherStub::withIdentityCallback(),
            $this->gerrit_server_factory
        );

        $gerrit_servers = ['IAmAServer'];
        $this->gerrit_server_factory
            ->method('getAvailableServersForProject')
            ->willReturn($gerrit_servers);

        $this->assertFalse($checker->canMigrate($this->project));
    }

    public function testCanMigrateReturnsFalseIfPlatformCanUseGerritAndGerritServersNotSet()
    {
        $checker = new GerritCanMigrateChecker(
            EventDispatcherStub::withCallback(static function (GerritCanMigrateEvent $event): GerritCanMigrateEvent {
                $event->platformCanUseGerrit();

                return $event;
            }),
            $this->gerrit_server_factory
        );

        $gerrit_servers = [];
        $this->gerrit_server_factory
            ->method('getAvailableServersForProject')
            ->willReturn($gerrit_servers);

        $this->assertFalse($checker->canMigrate($this->project));
    }

    public function testCanMigrateReturnsTrueIfPlatformCanUseGerritAndGerritServersSet()
    {
        $checker = new GerritCanMigrateChecker(
            EventDispatcherStub::withCallback(static function (GerritCanMigrateEvent $event): GerritCanMigrateEvent {
                $event->platformCanUseGerrit();

                return $event;
            }),
            $this->gerrit_server_factory
        );

        $gerrit_servers = ['IAmAServer'];
        $this->gerrit_server_factory
            ->method('getAvailableServersForProject')
            ->willReturn($gerrit_servers);

        $this->assertTrue($checker->canMigrate($this->project));
    }
}
