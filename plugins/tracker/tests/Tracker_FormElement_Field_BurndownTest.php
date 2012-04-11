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

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker');
}

class Tracker_FormElement_Field_Burndown_Test extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $id = $tracker_id = $parent_id = $name = $label = $description
            = $use_it = $scope = $required = $notifications = $rank = null;
        
        $this->tracker = mock('Tracker');
        $this->field   = new Tracker_FormElement_Field_Burndown($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank);
        
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
    
    public function _itRetrieveRemainingEffortEvolutionFromDao() {
        $sprint_tracker         = aTracker()->build();
        $sprint                 = anArtifact()->withTracker($sprint_tracker)->build();
        
        $artifact_link_field_id = 12;
        $artifact_link_field    = stub('Tracker_FormElement_Field_ArtifactLink')->getId()->returns($artifact_link_field_id);
        
        $task_ids               = array(54, 55);
        $linked_tasks           = array(anArtifact()->withId(54)->build(), anArtifact()->withId(55)->build());
        
        $dao          = stub('Tracker_FormElement_Field_BurndownDao')->searchRemainingEffort($artifact_link_field_id, $task_ids)->returns('it works');
        
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields()->returns(array($artifact_link_field));
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndownDao', 'getLinkedArtifacts', 'getFormElementFactory'));
        stub($field)->getBurndownDao()->returns($dao);
        stub($field)->getLinkedArtifacts($sprint)->returns($linked_tasks);
        stub($field)->getFormElementFactory()->returns($form_element_factory);
        
        $this->assertEqual($field->getRemainingEffortEvolution($sprint), 'it works');
    }
    
}

?>
