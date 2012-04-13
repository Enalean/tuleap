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

class Tracker_FormElement_Field_Burndown_FetchBurndownImageTest extends TuleapTestCase {

    protected $sprint_tracker_id;
    protected $sprint;
    protected $sprint_tracker;
    protected $artifact_link_field;
    protected $form_element_factory;
    protected $last_changeset;
    protected $field;
    protected $burndown;
    protected $timestamp;
    protected $duration;
    
    public function setUp() {
        parent::setUp();
        
        $this->sprint_tracker_id = 113;
        $this->sprint_tracker    = aTracker()->withId($this->sprint_tracker_id)->build();
        
        $this->timestamp            = 1334095200;
        $this->start_date_field     = stub('Tracker_FormElement_Field_Date');
        $this->start_date_changeset_value = stub('Tracker_Artifact_ChangesetValue_Date')->getTimestamp()->returns($this->timestamp);
        
        $this->duration           = 13;
        $this->duration_field     = stub('Tracker_FormElement_Field_Integer');
        $this->duration_changeset_value = stub('Tracker_Artifact_ChangesetValue_Integer')->getValue()->returns($this->duration);
        
        $this->sprint = mock('Tracker_Artifact');
        stub($this->sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($this->sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);
        stub($this->sprint)->getTracker()->returns($this->sprint_tracker);
        
        $this->form_element_factory = mock('Tracker_FormElementFactory');
        stub($this->form_element_factory)->getFormElementByName($this->sprint_tracker_id, 'start_date')->returns($this->start_date_field);
        stub($this->form_element_factory)->getFormElementByName($this->sprint_tracker_id, 'duration')->returns($this->duration_field);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);
        
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndown', 'displayErrorImage'));
        
        $this->burndown = mock('Tracker_Chart_Burndown');
        stub($this->field)->getBurndown()->returns($this->burndown);
    }
    
    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }
    
    public function itCreatesABurndownWithArtifactLinkedArtifactsAStartDateAndADuration() {
        $task_tracker_id = 120;        
        $task_tracker    = aTracker()->withId($task_tracker_id)->build();
        $task_54         = anArtifact()->withId(54)->withTracker($task_tracker)->build();
        
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        
        $linked_artifacts = array($task_54, $bug_55);
        stub($this->sprint)->getLinkedArtifacts()->returns($linked_artifacts);
        
        $this->field->expectOnce('getBurndown', array($linked_artifacts));
        $this->burndown->expectOnce('setStartDate', array($this->timestamp));
        $this->burndown->expectOnce('setDuration', array($this->duration));
        
        $this->field->fetchBurndownImage($this->sprint);
    }
    
    public function itDisplaysAMessageWhenThereAreNoLinkedArtifacts() {
        stub($this->sprint)->getLinkedArtifacts()->returns(array());
        
        $this->expectException();
        
        $this->field->fetchBurndownImage($this->sprint);
    }
    
    public function itDisplaysAMessageWhenThereAreNoStartDateField() {
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        
        $linked_artifacts = array($bug_55);
        stub($this->sprint)->getLinkedArtifacts()->returns($linked_artifacts);
        
        Tracker_FormElementFactory::clearInstance();
        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getFormElementByName($this->sprint_tracker_id, 'duration')->returns($this->duration_field);
        Tracker_FormElementFactory::setInstance($form_element_factory);
        
        $this->expectException();
        
        $this->field->fetchBurndownImage($this->sprint);
    }
    
    public function itDisplaysAMessageWhenThereAreNoDurationField() {
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        
        $linked_artifacts = array($bug_55);
        stub($this->sprint)->getLinkedArtifacts()->returns($linked_artifacts);
        
        Tracker_FormElementFactory::clearInstance();
        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getFormElementByName($this->sprint_tracker_id, 'start_date')->returns($this->start_date_field);
        Tracker_FormElementFactory::setInstance($form_element_factory);
        
        $this->expectException();
        
        $this->field->fetchBurndownImage($this->sprint);
    }

    public function itDisplaysAMessageWhenStartDateIsEmpty() {
        
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        
        $linked_artifacts = array($bug_55);
        
        
        // Empty timestamp
        $start_date_changeset_value = stub('Tracker_Artifact_ChangesetValue_Date')->getTimestamp()->returns('');

        $sprint = mock('Tracker_Artifact');
        stub($sprint)->getLinkedArtifacts()->returns($linked_artifacts);
        stub($sprint)->getValue($this->start_date_field)->returns($start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);
        
        $this->expectException();
        
        $this->field->fetchBurndownImage($sprint);
    }
    
    public function itDisplaysAMessageWhenDurationIsEmpty() {
        $bug_tracker_id = 126;
        $bug_tracker    = aTracker()->withId($bug_tracker_id)->build();
        $bug_55         = anArtifact()->withId(55)->withTracker($bug_tracker)->build();
        
        $linked_artifacts = array($bug_55);
            
        // Empty duration
        $duration_changeset_value = stub('Tracker_Artifact_ChangesetValue_Integer')->getValue()->returns(0);

        $sprint = mock('Tracker_Artifact');
        stub($sprint)->getLinkedArtifacts()->returns($linked_artifacts);
        stub($sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);
        
        $this->expectException();
        
        $this->field->fetchBurndownImage($sprint);
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
    
    public function setUp() {
        parent::setUp();
        
        $this->tracker_manager = mock('TrackerManager');
        $this->current_user    = mock('User');
        
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('fetchBurndownImage'));
    }
    
    public function tearDown() {
        parent::tearDown();
        Tracker_ArtifactFactory::clearInstance();
    }
    
    public function itShouldRenderGraphWhenShowBurndownFuncIsCalled() {
        $artifact_id = 999;
        
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => $artifact_id));
        
        $artifact        = stub('Tracker_Artifact');
        $artifactFactory = stub('Tracker_ArtifactFactory')->getArtifactById($artifact_id)->returns($artifact);
        Tracker_ArtifactFactory::setInstance($artifactFactory);
        
        $this->field->expectOnce('fetchBurndownImage', array($artifact));
        
        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }
    
    public function itMustNotBuildBurndownWhensrc_aidIsNotValid() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => '; DROP DATABASE mouuahahahaha!'));
        
        $artifactFactory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);
        
        $this->field->expectNever('fetchBurndownImage');
        
        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }
    
    public function itMustNotBuildBurndownWhenArtifactDoesNotExist() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => 999));
        
        $artifactFactory = stub('Tracker_ArtifactFactory')->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);
        
        $this->field->expectNever('fetchBurndownImage');
        
        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }
}

?>
