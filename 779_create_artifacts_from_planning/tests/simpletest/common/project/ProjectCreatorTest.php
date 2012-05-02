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

Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('Rule_ProjectName');
Mock::generate('Rule_ProjectFullName');
        
class ProjectCreatorTest extends UnitTestCase {
    
    public function testInvalidShortNameShouldRaiseException() {
        $creator = $this->GivenAProjectCreator();
        
        $this->ruleShortName->setReturnValue('isValid', false);
        $this->ruleShortName->setReturnValue('getErrorMessage', 'Yippee-ki-yay motherfucker');
        
        $this->expectException('Project_InvalidShortName_Exception');
        $creator->create('contains.point', 'sdf', array());
    }
    
    public function testInvalidFullNameShouldRaiseException() {
        $creator = $this->GivenAProjectCreator();
        
        $this->ruleShortName->setReturnValue('isValid', true);

        $this->ruleFullName->setReturnValue('isValid', false);
        $this->ruleFullName->setReturnValue('getErrorMessage', 'Yippee-ki-yay motherfucker');
        
        $this->expectException('Project_InvalidFullName_Exception');
        $creator->create('contains.point', 'sdf', array());
    }
    
    public function testCreationFailureShouldRaiseException() {
        $creator = $this->GivenAProjectCreator();

        $this->ruleShortName->setReturnValue('isValid', true);
        $this->ruleFullName->setReturnValue('isValid', true);

        $creator->setReturnValue('create_project', false);

        $this->expectException('Project_Creation_Exception');
        $creator->create('contains.point', 'sdf', array());
    }
    
    /**
     * @return ProjectCreator
     */
    private function GivenAProjectCreator() {
        $projectManager       = new MockProjectManager();
        $this->ruleShortName  = new MockRule_ProjectName();
        $this->ruleFullName   = new MockRule_ProjectFullName();
        
        $creator              = TestHelper::getPartialMock('ProjectCreator', array('create_project'));
        $creator->__construct($projectManager, $this->ruleShortName, $this->ruleFullName);
        
        return $creator;
    }
}

?>
