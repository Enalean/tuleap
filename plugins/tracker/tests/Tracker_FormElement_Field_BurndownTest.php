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

require_once dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php';
require_once dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_Burndown.class.php';
require_once dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_ArtifactLink.class.php';
require_once dirname(__FILE__).'/Test_Tracker_Builder.php';
require_once dirname(__FILE__).'/Test_Artifact_Builder.php';
require_once dirname(__FILE__).'/../include/Tracker/FormElement/dao/Tracker_FormElement_Field_BurndownDao.class.php';
require_once dirname(__FILE__).'/builders/aBurndownField.php';
require_once dirname(__FILE__).'/builders/aMockTracker.php';

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

class Tracker_FormElement_Field_Burndown_Test extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $id = $tracker_id = $parent_id = $name = $label = $description
            = $use_it = $scope = $required = $notifications = $rank = null;
        
        $this->hierarchy_factory = mock('Tracker_HierarchyFactory');
        $this->tracker           = mock('Tracker');
        $this->field             = new Tracker_FormElement_Field_Burndown($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank);
        
        stub($this->tracker)->getId()->returns(123);
        stub($this->hierarchy_factory)->getChildren(123)->returns(array());
        
        $this->field->setHierarchyFactory($this->hierarchy_factory);
        $this->field->setTracker($this->tracker);
        
        $this->missing_start_date_warning = 'Missing start date';
        $this->missing_duration_warning   = 'Missing duration';
        
        $this->setText($this->missing_start_date_warning, array('plugin_tracker', 'burndown_missing_start_date_warning'));
        $this->setText($this->missing_duration_warning, array('plugin_tracker', 'burndown_missing_duration_warning'));
    }
    
    public function itRendersNoWarningWhenTrackerHasAStartDateField() {
        stub($this->tracker)->hasFormElementWithNameAndType('start_date', 'date')->returns(true);
        $html = $this->field->fetchAdminFormElement();
        $this->assertNoPattern('/'.$this->missing_start_date_warning.'/', $html);
    }
    
    public function itRendersAWarningWhenTrackerHasNoStartDateField() {
        stub($this->tracker)->hasFormElementWithNameAndType('start_date', 'date')->returns(false);
        $html = $this->field->fetchAdminFormElement();
        $this->assertPattern('/'.$this->missing_start_date_warning.'/', $html);
    }
    
    public function itRendersNoWarningWhenTrackerHasADurationField() {
        stub($this->tracker)->hasFormElementWithNameAndType('duration', 'int')->returns(true);
        $html = $this->field->fetchAdminFormElement();
        $this->assertNoPattern('/'.$this->missing_duration_warning.'/', $html);
    }
    
    public function itRendersAWarningWhenTrackerHasNoDurationField() {
        stub($this->tracker)->hasFormElementWithNameAndType('duration', 'int')->returns(false);
        $html = $this->field->fetchAdminFormElement();
        $this->assertPattern('/'.$this->missing_duration_warning.'/', $html);
    }
    
    public function itCanGetLinkedArtifactIdsFromAnArtifactAndAFormFieldElementArtifact() {
        $sprint_tracker         = aTracker()->build();
        $last_changeset         = mock('Tracker_Artifact_Changeset');
        $sprint                 = anArtifact()->withTracker($sprint_tracker)->withChangesets(array($last_changeset))->build();
        
        $task_ids               = array(54, 55);
        $linked_tasks           = array(anArtifact()->withId(54)->build(), anArtifact()->withId(55)->build());
        
        $artifact_link_field_id = 12;
        $artifact_link_field    = stub('Tracker_FormElement_Field_ArtifactLink')->getId()->returns($artifact_link_field_id);
        stub($artifact_link_field)->getLinkedArtifacts()->returns($linked_tasks);
        
        $this->assertEqual($task_ids, $this->field->getLinkedArtifactIds($sprint, $artifact_link_field));
    } 
    
    public function itRetrieveRemainingEffortEvolutionFromDao() {
        $task_ids               = array(54, 55);
        $artifact_link_field_id = 12;
        $artifact_link_field    = stub('Tracker_FormElement_Field_ArtifactLink')->getId()->returns($artifact_link_field_id);
        
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields()->returns(array($artifact_link_field));
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndownDao', 'getFormElementFactory', 'getLinkedArtifactIds'));

        stub($field)->getFormElementFactory()->returns($form_element_factory);
        stub($field)->getLinkedArtifactIds()->returns($task_ids);

        $sprint = anArtifact()->withTracker(aTracker()->build())->build();
        
        $dao = mock('Tracker_FormElement_Field_BurndownDao');
        stub($field)->getBurndownDao()->returns($dao);
        $dao->expectOnce('searchRemainingEffort', array($artifact_link_field_id, $task_ids));
        
        $field->getRemainingEffortEvolution($sprint);
    }
    
}

class Tracker_FormElement_Field_Burndown_RemainingEffortTest extends TuleapTestCase {
    
    public function itRendersAWarningForAnyTrackerChildThatHasNoEffortField() {
        $missing_remaining_effort_warning = 'The following trackers does not have a "remaining_effort" Integer or Float field:';
        $this->setText($missing_remaining_effort_warning, array('plugin_tracker', 'burndown_missing_remaining_effort_warning'));
        
        $stories = aMockTracker()->withName('Stories')->withFormElement('remaining_effort', 'int')->build();
        $demos   = aMockTracker()->withName('Demos')->withFormElement('remaining_effort', 'float')->build();
        $bugs    = aMockTracker()->withName('Bugs')->withNoFormElement('remaining_effort')->build();
        $chores  = aMockTracker()->withName('Chores')->withFormElement('remaining_effort', 'date')->build();
        
        $children   = array($stories, $demos, $bugs, $chores);
        $tracker_id = 123;
        
        $this->tracker           = stub('Tracker')->getId()->returns($tracker_id);
        $this->hierarchy_factory = stub('Tracker_HierarchyFactory')->getChildren($tracker_id)->returns($children);
        $this->field             = aBurndownField()->withTracker($this->tracker)->withHierarchyFactory($this->hierarchy_factory)->build();
        
        $html = $this->field->fetchAdminFormElement();
        
        $this->assertPattern('/'.$missing_remaining_effort_warning.'/', $html);
        $this->assertNoPattern('/Stories/', $html);
        $this->assertPattern('/Bugs/', $html);
        $this->assertPattern('/Chores/', $html);
    }
}

?>
