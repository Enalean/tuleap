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

class Project_SOAPServerObjectTest extends Project_SOAPServer {
    public function isRequesterAdmin($sessionKey, $project_id) {
        parent::isRequesterAdmin($sessionKey, $project_id);
    }
}

class Project_SOAPServerGenericUserTest extends TuleapTestCase {

    /** @var Project_SOAPServerObjectTest */
    private $server;

    public function setUp() {
        parent::setUp();

        $this->group_id    = 154;
        $this->session_key = '123';
        $this->password    = 'pwd';

        $this->user = mock('PFUser');
        $this->user->setReturnValue('isLoggedIn', true);

        $this->admin  = mock('PFUser');
        $this->admin->setReturnValue('isLoggedIn', true);
        $this->admin->setReturnValue('isSuperUser', true);

        $user_manager = new MockUserManager();

        $project = new MockProject();

        $project_manager            = stub('ProjectManager')->getProject($this->group_id)->returns($project);
        $project_creator            = new MockProjectCreator();
        $this->generic_user_factory = mock('GenericUserFactory');
        $limitator                  = new MockSOAP_RequestLimitator();

        $this->server = partial_mock(
                'Project_SOAPServerObjectTest',
                array('isRequesterAdmin', 'addProjectMember', 'removeProjectMember'),
                array($project_manager, $project_creator, $user_manager, $this->generic_user_factory, $limitator)
        );

        stub($this->server)->isRequesterAdmin($this->session_key, $this->group_id)->returns(true);
        stub($this->generic_user_factory)->create($this->group_id, $this->password)->returns($this->user);
        stub($this->user)->getUserName()->returns('User1');
        stub($user_manager)->getCurrentUser()->returns($this->admin);
    }

    public function itCreatesANewGenericUser() {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns(null);

        expect($this->generic_user_factory)->create($this->group_id, $this->password)->once();
        expect($this->server)->addProjectMember()->once();

        $this->server->setProjectGenericUser($this->session_key, $this->group_id, $this->password);
    }

    public function itDoesNotRecreateAGenericUserIfItAlreadyExists() {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns($this->user);

        expect($this->generic_user_factory)->create($this->group_id, $this->password)->never();
        expect($this->server)->addProjectMember()->once();

        $this->server->setProjectGenericUser($this->session_key, $this->group_id, $this->password);
    }

    public function itUnsetsGenericUser() {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns($this->user);

        expect($this->server)->removeProjectMember()->once();

        $this->server->unsetGenericUser($this->session_key, $this->group_id);
    }

    public function itThrowsASoapFaultWhileUnsetingGenericUserIfItIsNotActivated() {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns(null);

        $this->expectException();

        $this->server->unsetGenericUser($this->session_key, $this->group_id);
    }
}

?>
