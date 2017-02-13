<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use TuleapTestCase;
use EventManager;

require_once dirname(__FILE__).'/../../bootstrap.php';

class GerritCanMigrateCheckerTest extends TuleapTestCase
{

    private $can_migrate_checker;
    private $gerrit_server_factory;

    public function setUp() {
        parent::setUp();

        $this->gerrit_server_factory = mock('Git_RemoteServer_GerritServerFactory');
        $this->can_migrate_checker   = new GerritCanMigrateChecker(
            EventManager::instance(),
            $this->gerrit_server_factory
        );

        $this->repository = aGitRepository()->withId(1)->build();
    }

    public function tearDown()
    {
        EventManager::clearInstance();
    }

    public function testCanMigrateReturnsFalseIfPlatformCannotUseGerritAndGerritServersNotSet()
    {
        $plugin = new GerritCanMigrateCheckerTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_cannot_use_gerrit',
            false
        );

        $gerrit_servers = array();
        stub($this->gerrit_server_factory)->getAvailableServersForProject()->returns($gerrit_servers);

        $this->assertFalse($this->can_migrate_checker->canMigrate($this->repository));
    }

    public function testCanMigrateReturnsFalseIfPlatformCannotUseGerritAndGerritServersSet()
    {
        $plugin = new GerritCanMigrateCheckerTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_cannot_use_gerrit',
            false
        );

        $gerrit_servers = array($this->repository);
        stub($this->gerrit_server_factory)->getAvailableServersForProject()->returns($gerrit_servers);

        $this->assertFalse($this->can_migrate_checker->canMigrate($this->repository));
    }

    public function testCanMigrateReturnsFalseIfPlatformCanUseGerritAndGerritServersNotSet()
    {
        $plugin = new GerritCanMigrateCheckerTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_can_use_gerrit',
            false
        );

        $gerrit_servers = array();
        stub($this->gerrit_server_factory)->getAvailableServersForProject()->returns($gerrit_servers);

        $this->assertFalse($this->can_migrate_checker->canMigrate($this->repository));
    }

    public function testCanMigrateReturnsTrueIfPlatformCanUseGerritAndGerritServersSet()
    {
        $plugin = new GerritCanMigrateCheckerTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_can_use_gerrit',
            false
        );

        $gerrit_servers = array('IAmAServer');
        stub($this->gerrit_server_factory)->getAvailableServersForProject()->returns($gerrit_servers);

        $this->assertTrue($this->can_migrate_checker->canMigrate($this->repository));
    }
}

class GerritCanMigrateCheckerTest_LDAP_FakePlugin
{

    public function git_event_platform_cannot_use_gerrit($params)
    {
        $params['platform_can_use_gerrit'] = false;
    }

    public function git_event_platform_can_use_gerrit($params)
    {
        $params['platform_can_use_gerrit'] = true;
    }
}
