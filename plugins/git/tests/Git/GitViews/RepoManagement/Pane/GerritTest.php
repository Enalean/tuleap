<?php

/**
 * Copyright (c) Enalean, 2012. All rights reserved
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

    public function setUp() {
        parent::setUp();

        $this->repository             = new GitRepository();
        $this->request                = mock('Codendi_Request');
        $this->driver = mock('Git_Driver_Gerrit');
    }

    public function testCanBeDisplayedReturnsFalseIfAuthTypeNotLdapAndGerritServersSet() {
        //set of type not ldap
        Config::set('sys_auth_type', 'not_ldap');

        $gerrit_servers         = array('IAmAServer');
        $pane = new GitViews_RepoManagement_Pane_Gerrit($this->repository, $this->request, $this->driver, $gerrit_servers, array());

        $this->assertFalse($pane->canBeDisplayed());
    }

    public function testCanBeDisplayedReturnsFalseIfAuthTypeLdapAndGerritServersNotSet() {
        //set of type ldap
        Config::set('sys_auth_type', Config::AUTH_TYPE_LDAP);

        $gerrit_servers = array();
        $pane = new GitViews_RepoManagement_Pane_Gerrit($this->repository, $this->request, $this->driver, $gerrit_servers, array());

        $this->assertFalse($pane->canBeDisplayed());
    }

    public function testCanBeDisplayedReturnsTrueIfAuthTypeLdapAndGerritServersSet() {
        //set of type ldap
        Config::set('sys_auth_type', Config::AUTH_TYPE_LDAP);

        $gerrit_servers = array('IAmAServer');
        $pane = new GitViews_RepoManagement_Pane_Gerrit($this->repository, $this->request, $this->driver, $gerrit_servers, array());

        $this->assertTrue($pane->canBeDisplayed());
    }
}
?>
