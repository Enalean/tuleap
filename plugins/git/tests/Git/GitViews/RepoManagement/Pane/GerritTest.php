<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once dirname(__FILE__).'/../../../../bootstrap.php';

class GitViews_RepoManagement_Pane_GerritTest extends TuleapTestCase {

    private $driver_factory;

    public function setUp() {
        parent::setUp();

        $this->repository     = new GitRepository();
        $this->request        = mock('Codendi_Request');
        $this->driver         = mock('Git_Driver_Gerrit');
        $this->driver_factory = mock('Git_Driver_Gerrit_GerritDriverFactory');

        stub($this->driver_factory)->getDriver()->returns($this->driver);
    }

    public function tearDown() {
        EventManager::clearInstance();
    }

    public function testCanBeDisplayedReturnsFalseIfPlatformCannotUseGerritAndGerritServersNotSet() {
        $plugin = new GitViews_RepoManagement_Pane_GerritTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_cannot_use_gerrit',
            false
        );

        $gerrit_servers = array();
        $pane = new GitViews_RepoManagement_Pane_Gerrit(
            $this->repository,
            $this->request,
            $this->driver_factory,
            $gerrit_servers,
            array()
        );

        $this->assertFalse($pane->canBeDisplayed());
    }

    public function testCanBeDisplayedReturnsFalseIfPlatformCannotUseGerritAndGerritServersSet() {
        $plugin = new GitViews_RepoManagement_Pane_GerritTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_cannot_use_gerrit',
            false
        );

        $gerrit_servers = array('IAmAServer');
        $pane = new GitViews_RepoManagement_Pane_Gerrit(
            $this->repository,
            $this->request,
            $this->driver_factory,
            $gerrit_servers,
            array()
        );

        $this->assertFalse($pane->canBeDisplayed());
    }

    public function testCanBeDisplayedReturnsFalseIfPlatformCanUseGerritAndGerritServersNotSet() {
        $plugin = new GitViews_RepoManagement_Pane_GerritTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_can_use_gerrit',
            false
        );

        $gerrit_servers = array();
        $pane = new GitViews_RepoManagement_Pane_Gerrit(
            $this->repository,
            $this->request,
            $this->driver_factory,
            $gerrit_servers,
            array()
        );

        $this->assertFalse($pane->canBeDisplayed());
    }

    public function testCanBeDisplayedReturnsTrueIfPlatformCanUseGerritAndGerritServersSet() {
        $plugin = new GitViews_RepoManagement_Pane_GerritTest_LDAP_FakePlugin();
        EventManager::instance()->addListener(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            $plugin,
            'git_event_platform_can_use_gerrit',
            false
        );

        $gerrit_servers = array('IAmAServer');
        $pane = new GitViews_RepoManagement_Pane_Gerrit(
            $this->repository,
            $this->request,
            $this->driver_factory,
            $gerrit_servers,
            array()
        );

        $this->assertTrue($pane->canBeDisplayed());
    }
}

class GitViews_RepoManagement_Pane_GerritTest_LDAP_FakePlugin {

    public function git_event_platform_cannot_use_gerrit($params) {
        $params['platform_can_use_gerrit'] = false;
    }

    public function git_event_platform_can_use_gerrit($params) {
        $params['platform_can_use_gerrit'] = true;
    }

}