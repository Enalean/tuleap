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
    const EFFORT_FIELD_TYPE = 'float';
    
    
    protected $sprint;
    protected $artifact_link_field;
    protected $form_element_factory;
    protected $last_changeset;
    protected $field;
    protected $dao;
    
    public function setUp() {
        parent::setUp();
        
        $sprint_tracker_id      = 113;
        $sprint_tracker         = aTracker()->withId($sprint_tracker_id)->build();
        $this->last_changeset         = mock('Tracker_Artifact_Changeset');
        $this->sprint = anArtifact()->withTracker($sprint_tracker)->withChangesets(array($this->last_changeset))->build();
        
        $artifact_link_field_id = 12;
        $this->artifact_link_field    = stub('Tracker_FormElement_Field_ArtifactLink')->getId()->returns($artifact_link_field_id);
        
        $this->form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields()->returns(array($this->artifact_link_field));
        
        $this->dao = mock('Tracker_FormElement_Field_BurndownDao');
        stub($this->dao)->searchRemainingEffort()->returns(array());
        
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndownDao', 'getFormElementFactory'));
        stub($this->field)->getFormElementFactory()->returns($this->form_element_factory);
        stub($this->field)->getBurndownDao()->returns($this->dao);
    }
    
    protected function trackerlinkedArtifacts($tracker_id, $artifact_ids) {
        $tracker = aTracker()->withId($tracker_id)->build();
        $artifacts=array();
        foreach($artifact_ids as $artifact_id) {
            $artifacts[$artifact_id] = anArtifact()->withId($artifact_id)->withTracker($tracker)->build(); 
        }
        return $artifacts;
    }
    
    public function itRetrieveRemainingEffortEvolutionFromDao() {
        $task_tracker_id = 120;
        $task_ids     = array(54, 55);
        $linked_tasks = $this->trackerlinkedArtifacts($task_tracker_id, $task_ids);
        stub($this->artifact_link_field)->getLinkedArtifacts($this->last_changeset)->returns($linked_tasks);
        
        $effort_field_id   = 35;
        $effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($effort_field_id);
        stub($this->form_element_factory)->getType($effort_field)->returns(self::EFFORT_FIELD_TYPE);
        stub($this->form_element_factory)->getFormElementByName($task_tracker_id, 'remaining_effort')->returns($effort_field);
        
        $this->dao->expectOnce('searchRemainingEffort', array($effort_field_id, self::EFFORT_FIELD_TYPE, $task_ids));
        
        $this->field->getRemainingEffortEvolution($this->sprint);
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
        stub($this->artifact_link_field)->getLinkedArtifacts($this->last_changeset)->returns($linked_artifacts);

        $tasks_effort_field_id   = 35;
        $tasks_effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($tasks_effort_field_id);
        stub($this->form_element_factory)->getType($tasks_effort_field)->returns(self::EFFORT_FIELD_TYPE);
        stub($this->form_element_factory)->getFormElementByName($task_tracker_id, 'remaining_effort')->returns($tasks_effort_field);
        
        $bugs_effort_field_id   = 37;
        $bugs_effort_field      = stub('Tracker_FormElement_Field_Float')->getId()->returns($bugs_effort_field_id);
        stub($this->form_element_factory)->getType($bugs_effort_field)->returns(self::EFFORT_FIELD_TYPE);
        stub($this->form_element_factory)->getFormElementByName($bug_tracker_id, 'remaining_effort')->returns($bugs_effort_field);
        
        $this->dao->expectAt(0, 'searchRemainingEffort', array($tasks_effort_field_id, self::EFFORT_FIELD_TYPE, $task_ids));
        $this->dao->expectAt(1, 'searchRemainingEffort', array($bugs_effort_field_id, self::EFFORT_FIELD_TYPE, $bug_ids));
        
        $this->field->getRemainingEffortEvolution($this->sprint);
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

class Tracker_FormElement_Field_Burndown_RequestProcessingTest extends TuleapTestCase {
    
    public function tearDown() {
        parent::tearDown();
        Tracker_ArtifactFactory::clearInstance();
    }
    
    public function itShouldRenderGraphWhenShowBurndownFuncIsCalled() {
        $artifact_id = 999;
        
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'aid'         => $artifact_id));
        
        $artifact = stub('Tracker_Artifact');
        $artifactFactory = stub('Tracker_ArtifactFactory')->getArtifactById($artifact_id)->returns($artifact);
        Tracker_ArtifactFactory::setInstance($artifactFactory);
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('fetchBurndownImage'));
        
        $field->expectOnce('fetchBurndownImage', array($artifact));
        
        $tracker_manager = mock('TrackerManager');
        $current_user    = mock('User');
        
        $field->process($tracker_manager, $request, $current_user);
        
    }
    
    public function itMustNotBuildBurndownWhenAidIsNotValid() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'aid'         => '; DROP DATABASE mouuahahahaha!'));
        
        $artifactFactory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('fetchBurndownImage'));
        
        $field->expectNever('fetchBurndownImage');
        
        $tracker_manager = mock('TrackerManager');
        $current_user    = mock('User');
        
        $field->process($tracker_manager, $request, $current_user);
    }
    
    public function itMustNotBuildBurndownWhenArtifactDoesNotExist() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'aid'         => 999));
        
        
        
        $field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('fetchBurndownImage'));
        
        $artifactFactory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);
        
        $field->expectNever('fetchBurndownImage');
        
        $tracker_manager = mock('TrackerManager');
        $current_user    = mock('User');
        
        $field->process($tracker_manager, $request, $current_user);
    }
}

?>
