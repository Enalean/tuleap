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
require_once dirname(__FILE__).'/builders/aMockHierarchyFactory.php';

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

class Tracker_FormElement_Field_Burndown_StartDateAndDurationTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $tracker_id = 123;
        
        $this->tracker           = aMockTracker()->withId($tracker_id)->build();
        $this->hierarchy_factory = aMockHierarchyFactory()->withNoChildrenForTrackerId($tracker_id)->build();
        $this->field             = aBurndownField()->withTracker($this->tracker)->withHierarchyFactory($this->hierarchy_factory)->build();
        
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
}

class Tracker_FormElement_Field_Burndown_RemainingEffortTest extends TuleapTestCase {
    
    public function itRetrieveRemainingEffortEvolutionFromDao() {
        $sprint_tracker_id      = 113;
        $last_changeset         = mock('Tracker_Artifact_Changeset');
        $sprint_tracker         = aTracker()->withId($sprint_tracker_id)->build();
        $sprint = anArtifact()->withTracker($sprint_tracker)->withChangesets(array($last_changeset))->build();
        
        $artifact_link_field_id = 12;
        $artifact_link_field    = stub('Tracker_FormElement_Field_ArtifactLink')->getId()->returns($artifact_link_field_id);
        
        $task_tracker_id = 120;
        $task_tracker = aTracker()->withId($task_tracker_id)->build();
        $task_ids     = array(54, 55);
        $task_54      = anArtifact()->withId(54)->withTracker($task_tracker)->build();
        $task_55      = anArtifact()->withId(55)->withTracker($task_tracker)->build();
        $linked_tasks = array($task_54, $task_55);
        stub($artifact_link_field)->getLinkedArtifacts($last_changeset)->returns($linked_tasks);
        
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields()->returns(array($artifact_link_field));
        
        $effort_field_id   = 35;
        $effort_field_type = 'float';
        $effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($effort_field_id);
        stub($form_element_factory)->getType($effort_field)->returns($effort_field_type);
        stub($form_element_factory)->getFormElementByName($task_tracker_id, 'remaining_effort')->returns($effort_field);
        
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndownDao', 'getFormElementFactory'));

        stub($field)->getFormElementFactory()->returns($form_element_factory);
        

        $dao = mock('Tracker_FormElement_Field_BurndownDao');
        stub($dao)->searchRemainingEffort()->returns(array());
        stub($field)->getBurndownDao()->returns($dao);
        $dao->expectOnce('searchRemainingEffort', array($effort_field_id, $effort_field_type, $task_ids));
        
        $field->getRemainingEffortEvolution($sprint);
    }
    
    public function itRetrieveRemainingEffortEvolutionFromSeveralSubTrackers() {
        $sprint_tracker_id      = 113;
        $last_changeset         = mock('Tracker_Artifact_Changeset');
        $sprint_tracker         = aTracker()->withId($sprint_tracker_id)->build();
        $sprint = anArtifact()->withTracker($sprint_tracker)->withChangesets(array($last_changeset))->build();
        
        $artifact_link_field_id = 12;
        $artifact_link_field    = stub('Tracker_FormElement_Field_ArtifactLink')->getId()->returns($artifact_link_field_id);
        
        
        $task_tracker_id = 120;
        $task_tracker    = aTracker()->withId($task_tracker_id)->build();
        $task_54         = anArtifact()->withId(54)->withTracker($task_tracker)->build();
        $task_ids        = array(54);
        
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        $bug_ids        = array(55);
        
        $linked_artifacts = array($task_54, $bug_55);
        stub($artifact_link_field)->getLinkedArtifacts($last_changeset)->returns($linked_artifacts);

        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields()->returns(array($artifact_link_field));
        
        $tasks_effort_field_id   = 35;
        $tasks_effort_field_type = 'float';
        $tasks_effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($tasks_effort_field_id);
        stub($form_element_factory)->getType($tasks_effort_field)->returns($tasks_effort_field_type);
        stub($form_element_factory)->getFormElementByName($task_tracker_id, 'remaining_effort')->returns($tasks_effort_field);
        
        $bugs_effort_field_id   = 37;
        $bugs_effort_field_type = 'float';
        $bugs_effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($bugs_effort_field_id);
        stub($form_element_factory)->getType($bugs_effort_field)->returns($bugs_effort_field_type);
        stub($form_element_factory)->getFormElementByName($bug_tracker_id, 'remaining_effort')->returns($bugs_effort_field);
        
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndownDao', 'getFormElementFactory'));

        stub($field)->getFormElementFactory()->returns($form_element_factory);
        

        $dao = mock('Tracker_FormElement_Field_BurndownDao');
        stub($field)->getBurndownDao()->returns($dao);
        stub($dao)->searchRemainingEffort()->returns(array());
        $dao->expectAt(0, 'searchRemainingEffort', array($tasks_effort_field_id, $tasks_effort_field_type, $task_ids));
        $dao->expectAt(1, 'searchRemainingEffort', array($bugs_effort_field_id, $bugs_effort_field_type, $bug_ids));
        
        $field->getRemainingEffortEvolution($sprint);
    }
}

class Tracker_FormElement_Field_Burndown_ConfigurationWarningsTest extends TuleapTestCase {
    
    public function itRendersAWarningForAnyTrackerChildThatHasNoEffortField() {
        $warning_message = 'Foo';
        $this->setText($warning_message, array('plugin_tracker', 'burndown_missing_remaining_effort_warning'));
        
        $stories = aMockTracker()->withName('Stories')->havingFormElementWithNameAndType('remaining_effort', array('int', 'float'))->build();
        $bugs    = aMockTracker()->withName('Bugs')->havingNoFormElement('remaining_effort')->build();
        $chores  = aMockTracker()->withName('Chores')->havingFormElementWithNameAndType('remaining_effort', array('int', 'date'))->build();
        
        $children   = array($stories, $bugs, $chores);
        $tracker_id = 123;
        
        $tracker           = aMockTracker()->withId($tracker_id)->build();
        $hierarchy_factory = aMockHierarchyFactory()->withChildrenForTrackerId($tracker_id, $children)->build();
        $field             = aBurndownField()->withTracker($tracker)->withHierarchyFactory($hierarchy_factory)->build();
        
        $html = $field->fetchAdminFormElement();
        
        $this->assertPattern('/'.$warning_message.'/', $html);
        $this->assertNoPattern('/Stories/', $html);
        $this->assertPattern('/Bugs/', $html);
        $this->assertPattern('/Chores/', $html);
    }
}

?>
