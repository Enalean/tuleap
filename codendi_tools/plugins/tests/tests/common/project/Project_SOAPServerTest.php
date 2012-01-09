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

Mock::generate('User');
Mock::generate('UserManager');

Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('ProjectCreator');

class Project_SOAPServerTest extends UnitTestCase {
    
    function testAddProjectWithoutAValidAdminSessionKeyShouldNotCreateProject() {
        $server = $this->GivenASOAPServer();
        
        $sessionKey      = '123';
        $adminSessionKey = '789';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;
        
        try {
            $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
            $this->assertTrue(false, "Creating a project without a valid admin session key should throw an soapfault");
        } catch (SoapFault $fault) {
            $this->assertTrue(true, "Should catch an exception");
        }
    }
    
    function testAddProjectShouldCreateAProject() {
        $server = $this->GivenASOAPServer();
        
        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;
        
        $project = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertIsA($project, 'Project');
    }
    
    /**
     *
     * @return Project_SOAPServer
     */
    private function GivenASOAPServer() {
        $user   = new MockUser();
        $user->setReturnValue('isLoggedIn', true);
        
        $another_user = new MockUser();
        $another_user->setReturnValue('isLoggedIn', true);
        
        $admin  = new MockUser();
        $admin->setReturnValue('isLoggedIn', true);
        $admin->setReturnValue('isSuperUser', true);
        
        $um     = new MockUserManager();
        $um->setReturnValue('getCurrentUser', $user, array('123'));
        $um->setReturnValue('getCurrentUser', $admin, array('456'));
        $um->setReturnValue('getCurrentUser', $another_user, array('789'));
        
        $pm     = new MockProjectManager();
        
        $template = new MockProject();
        $template->services = array();
        $pm->setReturnValue('getProject', $template, array(100));
        
        
        $project = new MockProject();
        $pc      = new MockProjectCreator();
        $pc->setReturnValue('create', $project, array('toto', 'Mon Toto', '*'));
        $pm->setReturnValue('activate', $project, array($project));
        
        $server = new Project_SOAPServer($pm, $pc, $um);
        return $server;
    }
}

?>
