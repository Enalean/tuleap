<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Chart_Burndown_Data_LinkedArtifactsTest extends TuleapTestCase {
    const EFFORT_FIELD_TYPE = 'float';
    
    protected $form_element_factory;
    protected $burndown_data;
    protected $dao;
    protected $current_user;
    
    public function setUp() {
        parent::setUp();
        
        $this->current_user = aUser()->build();
        
        $this->form_element_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->form_element_factory);
        
        $this->dao = mock('Tracker_Chart_Burndown_Data_LinkedArtifactsDao');
        stub($this->dao)->searchRemainingEffort()->returns(array());
        
        $this->burndown_data = TestHelper::getPartialMock('Tracker_Chart_Burndown_Data_LinkedArtifacts', array('getBurndownDao'));
        stub($this->burndown_data)->getBurndownDao()->returns($this->dao);
    }
    
    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }
    
    protected function trackerlinkedArtifacts($tracker_id, $artifact_ids) {
        $tracker   = aTracker()->withId($tracker_id)->build();
        $artifacts = array();
        foreach($artifact_ids as $artifact_id) {
            $artifacts[$artifact_id] = anArtifact()->withId($artifact_id)->withTracker($tracker)->build(); 
        }
        return $artifacts;
    }
    
    public function itRetrieveRemainingEffortEvolutionFromDao() {
        $task_tracker_id = 120;
        $task_ids     = array(54, 55);
        $linked_tasks = $this->trackerlinkedArtifacts($task_tracker_id, $task_ids);
        
        $effort_field_id   = 35;
        $effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($effort_field_id);
        stub($this->form_element_factory)->getType($effort_field)->returns(self::EFFORT_FIELD_TYPE);
        stub($this->form_element_factory)->getUsedFieldByNameForUser($task_tracker_id, 'remaining_effort', $this->current_user)->returns($effort_field);
        
        $this->dao->expectOnce('searchRemainingEffort', array($effort_field_id, self::EFFORT_FIELD_TYPE, $task_ids));
        
        $this->burndown_data->__construct($linked_tasks, 'remaining_effort', $this->current_user);
    }
    
    public function itRetrieveRemainingEffortEvolutionFromSeveralSubTrackers() {  
        $task_tracker_id = 120;
        $task_tracker    = aTracker()->withId($task_tracker_id)->build();
        $task_54         = anArtifact()->withId(54)->withTracker($task_tracker)->build();
        $task_ids        = array(54);
        
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        $bug_ids        = array(55);
        
        $linked_artifacts = array($task_54, $bug_55);

        $tasks_effort_field_id   = 35;
        $tasks_effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($tasks_effort_field_id);
        stub($this->form_element_factory)->getType($tasks_effort_field)->returns(self::EFFORT_FIELD_TYPE);
        stub($this->form_element_factory)->getUsedFieldByNameForUser($task_tracker_id, 'remaining_effort', $this->current_user)->returns($tasks_effort_field);
        
        $bugs_effort_field_id   = 37;
        $bugs_effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($bugs_effort_field_id);
        stub($this->form_element_factory)->getType($bugs_effort_field)->returns(self::EFFORT_FIELD_TYPE);
        stub($this->form_element_factory)->getUsedFieldByNameForUser($bug_tracker_id, 'remaining_effort', $this->current_user)->returns($bugs_effort_field);
        
        $this->dao->expectAt(0, 'searchRemainingEffort', array($tasks_effort_field_id, self::EFFORT_FIELD_TYPE, $task_ids));
        $this->dao->expectAt(1, 'searchRemainingEffort', array($bugs_effort_field_id, self::EFFORT_FIELD_TYPE, $bug_ids));
        
        $this->burndown_data->__construct($linked_artifacts, 'remaining_effort', $this->current_user);
    }
}

?>
