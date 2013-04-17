<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(dirname(__FILE__).'/../../include/system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php');

class SystemEvent_PLUGIN_LDAP_UPDATE_LOGINTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->um = mock('UserManager');
        $this->project_manager = mock('ProjectManager');
        $this->backend = mock('BackendSVN');
        $this->ldap_project_manager = mock('LDAP_ProjectManager');
        $this->system_event = aSystemEvent('SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN')->withParameters('101::102')->build();
        $this->system_event->injectDependencies($this->um, $this->backend, $this->project_manager, $this->ldap_project_manager);

        $user1 = mock('PFUser');
        $user1->setReturnValue('getAllProjects', array(201, 202));
        $user1->setReturnValue('isActive', true);
        $user2 = mock('PFUser');
        $user2->setReturnValue('getAllProjects', array(202, 203));
        $user2->setReturnValue('isActive', true);
        $this->um->setReturnValue('getUserById', $user1, array('101'));
        $this->um->setReturnValue('getUserById', $user2, array('102'));

        $this->prj1 = stub('Project')->getId()->returns(201);
        $this->prj2 = stub('Project')->getId()->returns(202);
        $this->prj3 = stub('Project')->getId()->returns(203);
        $this->project_manager->setReturnValue('getProject', $this->prj1, array(201));
        $this->project_manager->setReturnValue('getProject', $this->prj2, array(202));
        $this->project_manager->setReturnValue('getProject', $this->prj3, array(203));
    }

    function testUpdateShouldUpdateAllProjects() {
        $this->backend->expectCallCount('updateProjectSVNAccessFile', 3);
        expect($this->backend)->updateProjectSVNAccessFile($this->prj1)->at(0);
        expect($this->backend)->updateProjectSVNAccessFile($this->prj2)->at(1);
        expect($this->backend)->updateProjectSVNAccessFile($this->prj3)->at(2);

        stub($this->ldap_project_manager)->hasSVNLDAPAuth()->returns(true);

        $this->system_event->process();
    }

    function itSkipsProjectsThatAreNotManagedByLdap() {
        expect($this->backend)->updateProjectSVNAccessFile()->never();
        stub($this->ldap_project_manager)->hasSVNLDAPAuth()->returns(false);
        $this->system_event->process();
    }

    function itSkipsProjectsBasedOnProjectId() {
        expect($this->ldap_project_manager)->hasSVNLDAPAuth()->count(3);
        expect($this->ldap_project_manager)->hasSVNLDAPAuth(201)->at(0);
        expect($this->ldap_project_manager)->hasSVNLDAPAuth(202)->at(1);
        expect($this->ldap_project_manager)->hasSVNLDAPAuth(203)->at(2);

        $this->system_event->process();
    }
}
?>