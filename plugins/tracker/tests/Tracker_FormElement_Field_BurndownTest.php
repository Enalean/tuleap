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
        
        $task_ids     = array(54, 55);
        $linked_tasks = array(anArtifact()->withId(54)->build(), anArtifact()->withId(55)->build());
        stub($artifact_link_field)->getLinkedArtifacts($last_changeset)->returns($linked_tasks);
        
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields()->returns(array($artifact_link_field));
        
        $effort_field_id   = 35;
        $effort_field_type = 'float';
        $effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($effort_field_id);
        stub($form_element_factory)->getType($effort_field)->returns($effort_field_type);
        stub($form_element_factory)->getFormElementByName($sprint_tracker_id, 'remaining_effort')->returns($effort_field);
        
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndownDao', 'getFormElementFactory'));

        stub($field)->getFormElementFactory()->returns($form_element_factory);
        

        $dao = mock('Tracker_FormElement_Field_BurndownDao');
        stub($field)->getBurndownDao()->returns($dao);
        $dao->expectOnce('searchRemainingEffort', array($effort_field_id, $effort_field_type, $task_ids));
        
        $field->getRemainingEffortEvolution($sprint);
    }
}

class Tracker_FormElement_Field_Burndown_ConfigurationWarningsTest extends TuleapTestCase {
    
    public function itRendersAWarningForAnyTrackerChildThatHasNoEffortField() {
        $warning_message = 'Foo';
        $this->setText($warning_message, array('plugin_tracker', 'burndown_missing_remaining_effort_warning'));
        
        $stories = aMockTracker()->withName('Stories')->withFormElement('remaining_effort', 'int')->build();
        $demos   = aMockTracker()->withName('Demos')->withFormElement('remaining_effort', 'float')->build();
        $bugs    = aMockTracker()->withName('Bugs')->withNoFormElement('remaining_effort')->build();
        $chores  = aMockTracker()->withName('Chores')->withFormElement('remaining_effort', 'date')->build();
        
        $children   = array($stories, $demos, $bugs, $chores);
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
