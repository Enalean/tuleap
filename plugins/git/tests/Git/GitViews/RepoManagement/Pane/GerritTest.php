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

class GitViews_RepoManagement_Pane_GerritTest extends TuleapTestCase {
    
    public function testCanBeDisplayedReturnsFalseIfAuthTypeLdap() {
        //set of type not ldap
        Config::set('sys_auth_type', 'not_ldap');
                
        $repository = new GitRepository();
        $request = mock('Codendi_Request');
        $driver = mock('Git_Driver_Gerrit');
        $gerrit_servers = array('IAmAServer');
        
        $pane = new GitViews_RepoManagement_Pane_Gerrit($repository, $request, $driver, $gerrit_servers);
        
        $this->assertFalse($pane->canBeDisplayed());
    }
    
    public function testCanBeDisplayedReturnsTrueIfAuthTypeLdapAndGerritServersSet() {
        //set of type ldap
        Config::set('sys_auth_type', Git::SYS_AUTH_TYPE_LDAP);
                
        $repository = new GitRepository();
        $request = mock('Codendi_Request');
        $driver = mock('Git_Driver_Gerrit');
        $gerrit_servers = array('IAmAServer');
        
        $pane = new GitViews_RepoManagement_Pane_Gerrit($repository, $request, $driver, $gerrit_servers);
        
        $this->assertTrue($pane->canBeDisplayed());
    }
}
?>
