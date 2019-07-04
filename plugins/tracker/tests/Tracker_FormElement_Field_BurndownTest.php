<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        ForgeConfig::store();
        ForgeConfig::set('sys_logger_level', Logger::DEBUG);

        $tracker_id = 123;

        $this->tracker           = aMockeryTracker()->withId($tracker_id)->build();
        $this->hierarchy_factory = aMockHierarchyFactory()->withNoChildrenForTrackerId($tracker_id)->build();
        $this->field             = aBurndownField()->withTracker($this->tracker)->withHierarchyFactory($this->hierarchy_factory)->build();

        $this->missing_start_date_warning = 'Missing start date';
        $this->missing_duration_warning   = 'Missing duration';

        $GLOBALS['Language']->shouldReceive('getText')
            ->with('plugin_tracker', 'burndown_missing_start_date_warning')
            ->andReturn($this->missing_start_date_warning);

        $GLOBALS['Language']->shouldReceive('getText')
            ->with('plugin_tracker', 'burndown_missing_duration_warning')
            ->andReturn($this->missing_duration_warning);
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itRendersNoWarningWhenTrackerHasAStartDateField() {
        stub($this->tracker)->hasFormElementWithNameAndType('start_date', 'date')->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('remaining_effort', array('int', 'float', 'computed'))->returns(true);
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

class Tracker_FormElement_Field_Burndown_ConfigurationWarningsTest extends TuleapTestCase {

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        ForgeConfig::store();
        ForgeConfig::set('sys_logger_level', Logger::DEBUG);
    }

    public function tearDown()
    {
        parent::tearDown();
        ForgeConfig::restore();
    }

    public function itRendersAWarningForAnyTrackerChildThatHasNoEffortField()
    {
        $warning_message = 'Foo';
        $GLOBALS['Language']->shouldReceive('getText')
            ->with('plugin_tracker', 'burndown_missing_remaining_effort_warning')
            ->andReturn($warning_message);

        $bugs    = aMockeryTracker()->withName('Bugs')->havingNoFormElement('remaining_effort')->build();
        $chores  = aMockeryTracker()->withName('Chores')->havingFormElementWithNameAndType('remaining_effort', array('int', 'date'))->build();

        $children   = array($bugs, $chores);
        $tracker_id = 123;

        $tracker              = aMockeryTracker()->withId($tracker_id)->build();
        $hierarchy_factory    = aMockHierarchyFactory()->withChildrenForTrackerId($tracker_id, $children)->build();
        $field                = aBurndownField()
            ->withTracker($tracker)
            ->withHierarchyFactory($hierarchy_factory)
            ->build();

        $html = $field->fetchAdminFormElement();

        $this->assertPattern('/' . $warning_message . '/', $html);
        $this->assertPattern('/Bugs/', $html);
        $this->assertPattern('/Chores/', $html);
    }
}

class Tracker_FormElement_Field_Burndown_RequestProcessingTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->tracker_manager = \Mockery::spy(\TrackerManager::class);
        $this->current_user    = \Mockery::spy(\PFUser::class);

        $this->field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $tracker = mockery_stub(\Tracker::class)->isProjectAllowedToUseNature()->returns(false);
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

        $artifact        = Mockery::spy(\Tracker_Artifact::class);
        $artifactFactory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactById($artifact_id)->returns($artifact);
        Tracker_ArtifactFactory::setInstance($artifactFactory);

        $this->field->shouldReceive('fetchBurndownImage')->with($artifact, $this->current_user)->once();

        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }

    public function itMustNotBuildBurndownWhensrc_aidIsNotValid() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => '; DROP DATABASE mouuahahahaha!'));

        $artifactFactory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);

        $this->field->shouldReceive('fetchBurndownImage')->never();

        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }

    public function itMustNotBuildBurndownWhenArtifactDoesNotExist() {
        $request = new Codendi_Request(array('formElement' => 1234,
                                             'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                                             'src_aid'     => 999));

        $artifactFactory = mockery_stub(\Tracker_ArtifactFactory::class)->getArtifactById()->returns(null);
        Tracker_ArtifactFactory::setInstance($artifactFactory);

        $this->field->shouldReceive('fetchBurndownImage')->never();

        $this->field->process($this->tracker_manager, $request, $this->current_user);
    }
}

class Tracker_FormElement_Field_Burndown_CacheGeneration extends TuleapTestCase {
    /**
     * @var   Tracker_FormElement_Field_Date
     */
    private $start_date_field;

    /**
     * @var Tracker_Artifact
     */
    private $sprint;

    /**
     * @var Tracker_Artifact
     */
    private $sprint_tracker;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var Tracker_FormElement_Field_Burndown
     */
    private $field;

    /**
     * @varTracker_Chart_Burndown
     */
    private $burndown_view;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Tracker_Artifact_ChangesetValue_Date
     */
    private $start_date_changeset_value;

    /**
     * @var Tracker_FormElement_Field_Date
     */
    private $duration_field;

    /**
     * @var Tracker_Artifact_ChangesetValue_Integer
     */
    private $duration_changeset_value;

    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var Tracker_FormElement_Field_Computed
     */
    private $remaining_effort_field;

    /**
     * @var Tracker_FormElement_Field_ComputedDao
     */
    private $computed_dao;

    /**
     * @var SystemEventManager
     */
    private $event_manager;

    /**
     * @var Tracker_FormElement_Field_Computed
     */
    private $field_computed;

    private $sprint_tracker_id;
    private $timestamp;
    private $duration;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->sprint_tracker_id = 113;
        $this->sprint_tracker    = \Mockery::spy(\Tracker::class);
        stub($this->sprint_tracker)->getId()->returns($this->sprint_tracker_id);

        $this->timestamp                  = mktime(0, 0, 0, 7, 3, 2011);
        $this->start_date_field           = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->start_date_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Date::class)->getTimestamp()->returns(
            $this->timestamp
        );

        $this->duration                 = 5;
        $this->duration_field           = \Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $this->duration_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Integer::class)->getValue()->returns(
            $this->duration
        );

        $this->remaining_effort_field = Mockery::spy(\Tracker_FormElement_Field_Computed::class);

        $this->tracker = \Mockery::spy(\Tracker::class);

        $this->sprint = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($this->sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);

        $this->current_user = aUser()->build();

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($this->form_element_factory)->getUsedFieldByNameForUser(
            $this->sprint_tracker_id,
            'duration',
            $this->current_user
        )->returns($this->duration_field);
        stub($this->form_element_factory)->getUsedFieldByNameForUser(
            $this->sprint_tracker_id,
            'remaining_effort',
            $this->current_user
        )->returns($this->remaining_effort_field);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->field)->getLogger()->returns(\Mockery::spy(\Tuleap\Tracker\FormElement\BurndownLogger::class));

        $this->burndown_view = \Mockery::spy(\Tracker_Chart_Burndown::class);
        stub($this->field)->getBurndown()->returns($this->burndown_view);
        stub($this->field)->userCanRead()->returns(true);

        $this->computed_dao = \Mockery::spy(\Tracker_FormElement_Field_ComputedDao::class);
        stub($this->field)->getComputedDao()->returns($this->computed_dao);

        $this->event_manager = \Mockery::spy(\SystemEventManager::class);
        SystemEventManager::setInstance($this->event_manager);

        $this->field_computed = \Mockery::spy(\Tracker_FormElement_Field_Computed::class);
        stub($this->field_computed)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 3, 2011))->returns(10);
        stub($this->field_computed)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 4, 2011))->returns(9);
        stub($this->field_computed)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 5, 2011))->returns(8);
        stub($this->field_computed)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 6, 2011))->returns(7);
        stub($this->field_computed)->getComputedValue($this->current_user, $this->sprint, mktime(23, 59, 59, 7, 7, 2011))->returns(6);

        stub($this->sprint)->getValue()->returns(10);

        stub($this->tracker)->hasFormElementWithNameAndType('start_date', array('date'))->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('duration', array('int', 'float', 'computed'))->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('remaining_effort', array('int', 'float', 'computed'))->returns(true);
    }

    public function tearDown()
    {
        SystemEventManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();

        parent::tearDown();
    }

    public function itVerifiesCacheIsCompleteForBurndownWhenTrackerHasNoRemainingEffortField()
    {

        stub($this->form_element_factory)->getComputableFieldByNameForUser('*', 'remaining_effort', $this->current_user)->returns($this->field_computed);
        stub($this->form_element_factory)->getUsedFieldByNameForUser('*', 'start_date', $this->current_user)->returns($this->start_date_field);

        stub($this->sprint)->getTracker()->returns($this->tracker);

        $time_period = new TimePeriodWithoutWeekEnd($this->timestamp, $this->duration);

        $this->field->getBurndownData($this->sprint, $this->current_user, $time_period);

        expect($this->event_manager)->createEvent()->never();
    }

    public function itVerifiesCacheIsCompleteForBurndownWhenStartDateIsEmpty()
    {
        stub($this->form_element_factory)->getDateFieldByNameForUser(
            $this->tracker,
            array('date'),
            'start_date'
        )->returns(
            null
        );

        stub($this->form_element_factory)->getNumericFieldByNameForUser(
            $this->tracker,
            $this->current_user,
            'duration'
        )->returns(
            $this->duration_field
        );

        stub($this->form_element_factory)->getNumericFieldByNameForUser(
            $this->tracker,
            $this->current_user,
            'remaining_effort'
        )->returns(
            $this->field_computed
        );

        stub($this->sprint)->getTracker()->returns($this->tracker);
        stub($this->computed_dao)->getCachedDays()->returns(array('cached_days' => 60));
        stub($this->remaining_effort_field)->getId()->returns(10);
        stub($this->field)->isCacheBurndownAlreadyAsked()->returns(false);

        $this->expectException('Tracker_FormElement_Chart_Field_Exception');

        $time_period = new TimePeriodWithoutWeekEnd($this->timestamp, $this->duration);

        $this->field->getBurndownData($this->sprint, $this->current_user, $time_period);

        expect($this->event_manager)->createEvent()->never();
    }
}
