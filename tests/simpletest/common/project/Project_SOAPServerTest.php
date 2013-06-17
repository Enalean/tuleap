<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/project/Project_SOAPServer.class.php';
require_once 'common/user/GenericUserFactory.class.php';

Mock::generate('PFUser');
Mock::generate('UserManager');

Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('ProjectCreator');
Mock::generate('SOAP_RequestLimitator');

class Project_SOAPServerTest extends TuleapTestCase {
    
    function testAddProjectShouldFailWhenRequesterIsNotProjectAdmin() {
        $server = $this->GivenASOAPServerWithBadTemplate();
        
        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 101;
        
        // We don't care about the exception details
        $this->expectException();
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }
    
    /**
     *
     * @return Project_SOAPServer
     */
    private function GivenASOAPServerWithBadTemplate() {
        $server = $this->GivenASOAPServer();
        
        $this->user->setReturnValue('isMember', false);
        
        $template = new MockProject();
        $template->setReturnValue('isTemplate', false);
        
        $this->pm->setReturnValue('getProject', $template, array(101));
        
        return $server;
    }

    function testAddProjectWithoutAValidAdminSessionKeyShouldNotCreateProject() {
        $server = $this->GivenASOAPServerReadyToCreate();
        
        $sessionKey      = '123';
        $adminSessionKey = '789';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;
        
        $this->expectException('SoapFault');
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    
    function testAddProjectShouldCreateAProject() {
        $server = $this->GivenASOAPServerReadyToCreate();
        
        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;
        
        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEqual($projectId, 3459);
    }
    
    function testAddProjectShouldFailIfQuotaExceeded() {
        $server = $this->GivenASOAPServerReadyToCreate();
        $this->limitator->throwOn('logCallTo', new SOAP_NbRequestsExceedLimit_Exception());
        
        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;
        
        $this->expectException('SoapFault');
        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEqual($projectId, 3459);
    }
    
    /**
     *
     * @return Project_SOAPServer
     */
    private function GivenASOAPServerReadyToCreate() {
        $server = $this->GivenASOAPServer();
        
        $another_user = mock('PFUser');
        $another_user->setReturnValue('isLoggedIn', true);
        
        $this->um->setReturnValue('getCurrentUser', $another_user, array('789'));
        
        $template = new MockProject();
        $template->services = array();
        $template->setReturnValue('isTemplate', true);
        $this->pm->setReturnValue('getProject', $template, array(100));
        
        $new_project = new MockProject();
        $new_project->setReturnValue('getID', 3459);
        $this->pc->setReturnValue('create', $new_project, array('toto', 'Mon Toto', '*'));
        $this->pm->setReturnValue('activate', $new_project, true);
        
        return $server;
    }
    
    private function GivenASOAPServer() {
        $this->user = mock('PFUser');
        $this->user->setReturnValue('isLoggedIn', true);
        
        $admin  = mock('PFUser');
        $admin->setReturnValue('isLoggedIn', true);
        $admin->setReturnValue('isSuperUser', true);
        
        $this->um = new MockUserManager();
        $this->um->setReturnValue('getCurrentUser', $this->user, array('123'));
        $this->um->setReturnValue('getCurrentUser', $admin, array('456'));
        
        $this->pm        = new MockProjectManager();
        $this->pc        = new MockProjectCreator();
        $this->guf       = mock('GenericUserFactory');
        $this->limitator = new MockSOAP_RequestLimitator();
        $server          = new Project_SOAPServer($this->pm, $this->pc, $this->um, $this->guf, $this->limitator);
        return $server;
    }
}

?>
