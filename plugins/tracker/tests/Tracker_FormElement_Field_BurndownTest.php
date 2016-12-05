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
require_once('bootstrap.php');

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
    protected $burndown_view;
    protected $timestamp;
    protected $duration;
    
    public function setUp() {
        parent::setUp();
        
        $this->sprint_tracker_id = 113;
        $this->sprint_tracker    = aTracker()->withId($this->sprint_tracker_id)->build();
        
        $this->timestamp            = mktime(0, 0, 0, 7, 3, 2011);
        $this->start_date_field     = stub('Tracker_FormElement_Field_Date');
        $this->start_date_changeset_value = stub('Tracker_Artifact_ChangesetValue_Date')->getTimestamp()->returns($this->timestamp);
        
        $this->duration           = 5;
        $this->duration_field     = stub('Tracker_FormElement_Field_Integer');
        $this->duration_changeset_value = stub('Tracker_Artifact_ChangesetValue_Integer')->getValue()->returns($this->duration);
        
        $this->sprint = mock('Tracker_Artifact');
        stub($this->sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($this->sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);
        stub($this->sprint)->getTracker()->returns($this->sprint_tracker);
        
        $this->current_user = aUser()->build();
        
        $this->form_element_factory = mock('Tracker_FormElementFactory');
        stub($this->form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'start_date', $this->current_user)->returns($this->start_date_field);
        stub($this->form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'duration', $this->current_user)->returns($this->duration_field);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);
        
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndown', 'displayErrorImage', 'userCanRead', 'getProperty'));
        
        $this->burndown_view = mock('Tracker_Chart_Burndown');
        stub($this->field)->getBurndown()->returns($this->burndown_view);
        stub($this->field)->userCanRead()->returns(true);
    }
    
    public function tearDown() {
        parent::tearDown();
        Tracker_FormElementFactory::clearInstance();
    }
    
    public function _itFetchDataFromStartDateToDuration() {
        $field = mock('Tracker_FormElement_Field_Float');
        stub($field)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 3, 2011))->returns(10);
        stub($field)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 4, 2011))->returns(9);
        stub($field)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 5, 2011))->returns(8);
        stub($field)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 6, 2011))->returns(7);
        stub($field)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 7, 2011))->returns(6);
        stub($this->form_element_factory)->getComputableFieldByNameForUser($this->sprint_tracker_id, 'remaining_effort', $this->current_user)->returns($field);
        
        $data = $this->field->getBurndownData($this->sprint, $this->current_user, $this->timestamp, $this->duration);
        $this->assertEqual($data->getRemainingEffort(), array(10,9,8,7,6));
    }

    public function itCreatesABurndownWithArtifactLinkedArtifactsAStartDateAndADuration() {
        $time_period    = new TimePeriodWithWeekEnd($this->timestamp, $this->duration);
        $burndown_data  = new Tracker_Chart_Data_Burndown($time_period);
        $this->field    = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndown', 'displayErrorImage', 'userCanRead', 'getBurndownData'));
        $this->burndown_view = mock('Tracker_Chart_BurndownView');
        
        stub($this->field)->getBurndown($burndown_data)->returns($this->burndown_view);
        stub($this->field)->userCanRead()->returns(true);
        stub($this->field)->getBurndownData($this->sprint, $this->current_user, $this->timestamp, $this->duration)->returns($burndown_data);
        
        $this->burndown_view->expectOnce('display');
        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }
    
    public function _itDisplaysAMessageWhenThereAreNoLinkedArtifacts() {
        stub($this->sprint)->getLinkedArtifacts()->returns(array());
        
        $this->expectException(new Tracker_FormElement_Field_BurndownException('burndown_no_linked_artifacts'));
        
        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }
    
    private function GivenOneLinkedArtifact() {
        $bug_55           = anArtifact()->withId(55)->withTracker(aTracker()->build())->build();
        return array($bug_55);
    }
    
    private function GivenSprintHasOneLinkedArtifact() {
        stub($this->sprint)->getLinkedArtifacts()->returns($this->GivenOneLinkedArtifact());        
    }
    
    private function GivenFormElementFactoryHasOnlyDurationField() {
        Tracker_FormElementFactory::clearInstance();
        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'duration', $this->current_user)->returns($this->duration_field);
        Tracker_FormElementFactory::setInstance($form_element_factory);
    }
    
    public function itDisplaysAMessageWhenThereAreNoStartDateField() {
        $this->GivenSprintHasOneLinkedArtifact();
        
        $this->GivenFormElementFactoryHasOnlyDurationField();
        
        $this->expectException(new Tracker_FormElement_Field_BurndownException(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
        ));
        
        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }
    
    private function GivenFormElementFactoryHasOnlyStartDateField() {
        Tracker_FormElementFactory::clearInstance();
        $form_element_factory = mock('Tracker_FormElementFactory');
        stub($form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'start_date', $this->current_user)->returns($this->start_date_field);
        Tracker_FormElementFactory::setInstance($form_element_factory);
    }
    
    public function itDisplaysAMessageWhenThereAreNoDurationField() {
        $this->GivenSprintHasOneLinkedArtifact();
        
        $this->GivenFormElementFactoryHasOnlyStartDateField();
        
        $this->expectException(new Tracker_FormElement_Field_BurndownException(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
        ));
        
        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }

    public function itDisplaysAMessageWhenStartDateIsEmpty() {
        // Empty timestamp
        $start_date_changeset_value = stub('Tracker_Artifact_ChangesetValue_Date')->getTimestamp()->returns('');

        $sprint = mock('Tracker_Artifact');
        stub($sprint)->getLinkedArtifacts()->returns($this->GivenOneLinkedArtifact());
        stub($sprint)->getValue($this->start_date_field)->returns($start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);

        $this->expectException(new Tracker_FormElement_Field_BurndownException(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_start_date_warning')
        ));
        
        $this->field->fetchBurndownImage($sprint, $this->current_user);
    }
    
    public function itDisplaysAMessageWhenDurationIsEmpty() {
        // Empty duration
        $duration_changeset_value = stub('Tracker_Artifact_ChangesetValue_Integer')->getValue()->returns(0);

        $sprint = mock('Tracker_Artifact');
        stub($sprint)->getLinkedArtifacts()->returns($this->GivenOneLinkedArtifact());
        stub($sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);

        $this->expectException(new Tracker_FormElement_Field_BurndownException(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning')
        ));
        
        $this->field->fetchBurndownImage($sprint, $this->current_user);
    }

    public function itDisplaysAnErrorIfUserDoesntHaveThePermissionToAccessTheBurndownField() {
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('getBurndown', 'displayErrorImage', 'userCanRead'));
        stub($this->field)->userCanRead($this->current_user)->returns(false);
        
        $this->expectException(new Tracker_FormElement_Field_BurndownException(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_permission_denied')
        ));

        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }
}

class Tracker_FormElement_Field_Burndown_ConfigurationWarningsTest extends TuleapTestCase {
    
    public function itRendersAWarningForAnyTrackerChildThatHasNoEffortField() {
        $warning_message = 'Foo';
        $this->setText($warning_message, array('plugin_tracker', 'burndown_missing_remaining_effort_warning'));
        
        $stories = aMockTracker()->withName('Stories')->havingFormElementWithNameAndType('remaining_effort', array('int', 'float', 'computed'))->build();
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
        $this->current_user    = mock('PFUser');
        
        $this->field = TestHelper::getPartialMock('Tracker_FormElement_Field_Burndown', array('fetchBurndownImage'));

        $tracker = stub('Tracker')->isProjectAllowedToUseNature()->returns(false);
        $this->field->setTracker($tracker);
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
        
        $this->field->expectOnce('fetchBurndownImage', array($artifact, $this->current_user));
        
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
