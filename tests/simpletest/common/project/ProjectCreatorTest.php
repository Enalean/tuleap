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

require_once 'common/project/ProjectCreator.class.php';
require_once('common/language/BaseLanguage.class.php');

Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('BaseLanguage');
Mock::generate('UserManager');
Mock::generate('ProjectManager');
Mock::generate('SystemEventManager');

class ProjectCreatorTest_BaseLanguage extends MockBaseLanguage {
    
    public function getText($section, $name, $args = array()) {
        $args = implode($args, ',');
        return "$section.$name($args)";
    }

}

class ProjectCreatorTest extends UnitTestCase {

    public function setUp(){
        $GLOBALS['Language'] = new ProjectCreatorTest_BaseLanguage();

        $this->event_manager = new MockSystemEventManager();
        $this->event_manager->setReturnValue('isUserNameAvailable', true);
        $this->event_manager->setReturnValue('isProjectNameAvailable', true);
        SystemEventManager::setInstance($this->event_manager);

        $this->project_manager = new MockProjectManager();
        $this->project_manager->setReturnValue('getProjectByUnixName', null);
        ProjectManager::setInstance($this->project_manager);

        $this->user_manager = new MockUserManager();
        $this->user_manager->setReturnValue('getUserByUserName', null);
        UserManager::setInstance($this->user_manager);
    }

    public function tearDown(){
        UserManager::clearInstance();
        ProjectManager::clearInstance();
        SystemEventManager::clearInstance();
        unset($GLOBALS['Language']);
    }

    public function testInvalidShortNameShouldRaiseException() {
        $creator = $this->GivenAProjectCreator();
        
        $this->expectException('Project_InvalidShortName_Exception');
        $creator->create('contains.point', 'sdf', array());
    }
    
    public function testInvalidFullNameShouldRaiseException() {
        $creator = $this->GivenAProjectCreator();
        
        $this->expectException('Project_InvalidFullName_Exception');
        $creator->create('shortname', 'a', array()); // a is too short
    }
    
    public function testCreationFailureShouldRaiseException() {
        $creator = $this->GivenAProjectCreator();

        $this->expectException('Project_Creation_Exception');
        $creator->create('shortname', 'Valid Full Name', array());
    }
    
    /**
     * @return ProjectCreator
     */
    private function GivenAProjectCreator() {
        $projectManager       = new MockProjectManager();
        
        $creator = TestHelper::getPartialMock('ProjectCreator', array('createProject'));
        $creator->__construct($projectManager, ReferenceManager::instance());
        
        return $creator;
    }
}

?>
