<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\BurndownFieldPresenter;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

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

class Tracker_FormElement_Field_Burndown_JPGraphRender extends TuleapTestCase
{
    /**
     * @var \Tracker
     */
    private $tracker;
    /**
     * @var Tracker_FormElement_Field_Burndown
     */
    private $burndown_field;

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Tracker_Artifact_ChangesetValue
     */
    private $changesetValue;

    private $tracker_id;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $_SERVER['REQUEST_URI'] = '/plugins/tracker';

        $this->tracker    = \Mockery::spy(\Tracker::class);
        $this->tracker_id = 101;
        stub($this->tracker)->getId()->returns($this->tracker_id);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->artifact)->getTracker()->returns($this->tracker);

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $this->burndown_field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $logger = \Mockery::spy(\Tuleap\Tracker\FormElement\BurndownLogger::class);
        stub($this->burndown_field)->getLogger()->returns($logger);
        stub($this->burndown_field)->fetchBurndownReadOnly($this->artifact)->returns('<div id="burndown-chart"></div>');

        $this->user = \Mockery::spy(\PFUser::class);
        stub($this->burndown_field)->isCacheBurndownAlreadyAsked()->returns(false);
    }

    private function getAStartDateField($value)
    {
        $start_date_field           = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $start_date_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Date::class)->getTimestamp()->returns($value);
        stub($this->artifact)->getValue($start_date_field)->returns($start_date_changeset_value);

        stub($this->form_element_factory)->getDateFieldByNameForUser(
            $this->tracker,
            $this->user,
            'start_date'
        )->returns(
            $start_date_field
        );

        stub($start_date_field)->userCanRead()->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('start_date', array('date'))->returns(true);
    }

    private function getADurationField($value)
    {
        $duration_field           = Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $duration_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Integer::class)->getValue()->returns($value);
        stub($this->artifact)->getValue($duration_field)->returns($duration_changeset_value);

        stub($this->form_element_factory)->getNumericFieldByNameForUser(
            $this->tracker,
            $this->user,
            'duration'
        )->returns($duration_field);

        stub($duration_field)->userCanRead()->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('duration', array('int', 'float', 'computed'))->returns(true);
    }

    public function itRendersAJPGraphImageWhenBurndownHasAStartDateAndADuration()
    {
        stub($this->user)->isAdmin()->returns(true);
        stub($this->burndown_field)->getCurrentUser()->returns($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);
        $this->assertNotEqual(
            $result, '<img src="/plugins/tracker/?func=show_burndown" alt="" width="640" height="480" />'
        );
    }

    public function itRendersAJPGraphBurndownErrorWhenUserCantReadBurndownField()
    {
        stub($this->burndown_field)->getCurrentUser()->returns($this->user);
        stub($this->burndown_field)->userCanRead()->returns(false);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_permission_denied')
            )
        );
        $this->burndown_field->fetchBurndownImage($this->artifact, $this->user);
    }

    public function itRendersAJPGraphBurndownErrorWhenDurationIsEmpty()
    {
        stub($this->user)->isAdmin()->returns(true);
        stub($this->burndown_field)->getCurrentUser()->returns($this->user);
        stub($this->burndown_field)->userCanRead()->returns(true);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = null;
        $this->getADurationField($duration);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning')
            )
        );

        $this->burndown_field->fetchBurndownImage($this->artifact, $this->user);
    }

    public function itRendersAJPGraphBurndownErrorWhenDurationIsTooShort()
    {
        stub($this->user)->isAdmin()->returns(true);
        stub($this->burndown_field)->getCurrentUser()->returns($this->user);
        stub($this->burndown_field)->userCanRead()->returns(true);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = 1;
        $this->getADurationField($duration);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_duration_too_short')
            )
        );

        $this->burndown_field->fetchBurndownImage($this->artifact, $this->user);
    }

    public function tearDown()
    {
        Tracker_FormElementFactory::clearInstance();
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }
}

class Tracker_FormElement_Field_Burndown_D3Render extends TuleapTestCase
{
    /**
     * @var string
     */
    private $old_user_theme;
    /**
     * @var \Tracker
     */
    private $tracker;
    /**
     * @var Tracker_FormElement_Field_Burndown
     */
    private $burndown_field;

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var Tracker_Artifact_ChangesetValue
     */
    private $changesetValue;

    private $tracker_id;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $_SERVER['REQUEST_URI'] = '/plugins/tracker';

        $this->old_user_theme      = isset($GLOBALS['sys_user_theme']) ? $GLOBALS['sys_user_theme'] : null;
        $GLOBALS['sys_user_theme'] = 'BurningParrot';

        $GLOBALS['Language'] = \Mockery::spy(\BaseLanguage::class);

        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->tracker_id = 101;
        stub($this->tracker)->getId()->returns($this->tracker_id);

        $this->artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->artifact)->getTracker()->returns($this->tracker);

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $this->burndown_field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $logger = \Mockery::spy(\Tuleap\Tracker\FormElement\BurndownLogger::class);
        stub($this->burndown_field)->getLogger()->returns($logger);

        $this->user = \Mockery::spy(\PFUser::class);
        stub($this->burndown_field)->isCacheBurndownAlreadyAsked()->returns(false);
    }

    public function tearDown()
    {
        $GLOBALS['sys_user_theme'] = $this->old_user_theme;
        Tracker_FormElementFactory::clearInstance();
        unset($_SERVER['REQUEST_URI']);
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    private function getAStartDateField($value)
    {
        $start_date_field = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $start_date_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Date::class)->getTimestamp()->returns($value);
        stub($this->artifact)->getValue($start_date_field)->returns($start_date_changeset_value);

        stub($this->form_element_factory)->getDateFieldByNameForUser(
            $this->tracker,
            $this->user,
            'start_date'
        )->returns(
            $start_date_field
        );

        stub($start_date_field)->userCanRead()->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('start_date', array('date'))->returns(true);
    }

    private function getADurationField($value)
    {
        $duration_field = Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $duration_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Integer::class)->getValue()->returns($value);
        stub($this->artifact)->getValue($duration_field)->returns($duration_changeset_value);

        stub($this->form_element_factory)->getNumericFieldByNameForUser(
            $this->tracker,
            $this->user,
            'duration'
        )->returns($duration_field);

        stub($duration_field)->userCanRead()->returns(true);
        stub($this->tracker)->hasFormElementWithNameAndType('duration', array('int', 'float', 'computed'))->returns(true);
    }

    public function itTestButtonForceCacheGenerationIsNotPresentWhenStartDateIsNotSet()
    {
        stub($this->user)->isAdmin()->returns(true);
        stub($this->burndown_field)->getCurrentUser()->returns($this->user);

        $timestamp = null;
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        stub($this->burndown_field)->renderPresenter()->returns('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);
        $this->assertEqual(
            $result, '<div id="burndown-chart"></div>'
        );
    }

    public function itTestButtonForceCacheGenerationIsNotPresentDurationIsNotSet()
    {
        stub($this->user)->isAdmin()->returns(true);
        stub($this->burndown_field)->getCurrentUser()->returns($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = null;
        $this->getADurationField($duration);

        stub($this->burndown_field)->renderPresenter()->returns('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);
        $this->assertEqual(
            $result, '<div id="burndown-chart"></div>'
        );
    }
}

class Tracker_FormElement_Field_Burndown_FetchBurndownImageTest extends TuleapTestCase
{
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

    /**
     * @var Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever
     */
    private $field_retriever;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        ForgeConfig::store();
        ForgeConfig::set('sys_logger_level', Logger::DEBUG);

        $this->sprint_tracker_id = 113;
        $this->sprint_tracker    = \Mockery::spy(\Tracker::class);
        stub($this->sprint_tracker)->getId()->returns($this->sprint_tracker_id);

        $this->timestamp            = mktime(0, 0, 0, 7, 3, 2011);
        $this->start_date_field     = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->start_date_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Date::class)->getTimestamp()->returns($this->timestamp);

        $this->duration           = 5;
        $this->duration_field     = Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $this->duration_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Integer::class)->getValue()->returns($this->duration);

        $this->sprint = \Mockery::spy(\Tracker_Artifact::class);
        stub($this->sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($this->sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);
        stub($this->sprint)->getTracker()->returns($this->sprint_tracker);

        $this->current_user = aUser()->build();

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($this->form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'start_date', $this->current_user)->returns($this->start_date_field);
        stub($this->form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'duration', $this->current_user)->returns($this->duration_field);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $this->field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->burndown_view = \Mockery::spy(\Tracker_Chart_Burndown::class);

        $logger = \Mockery::spy(\Tuleap\Tracker\FormElement\BurndownLogger::class);
        stub($this->field)->getLogger()->returns($logger);
        stub($this->field)->getBurndown()->returns($this->burndown_view);
        stub($this->field)->userCanRead()->returns(true);

        stub($this->sprint_tracker)->hasFormElementWithNameAndType('start_date', array('date'))->returns(true);
        stub($this->sprint_tracker)->hasFormElementWithNameAndType('duration', array('int', 'float', 'computed'))->returns(true);
        stub($this->sprint_tracker)->hasFormElementWithNameAndType('remaining_effort', array('int', 'float', 'computed'))->returns(true);

        $this->field_retriever = new ChartConfigurationFieldRetriever(
            \Mockery::spy(\Tracker_FormElementFactory::class),
            \Mockery::spy(\Logger::class)
        );

        stub($this->field)->getBurdownConfigurationFieldRetriever()->returns($this->field_retriever);

        $this->system_event_manager = Mockery::spy(SystemEventManager::class);
        SystemEventManager::setInstance($this->system_event_manager);

        $this->system_event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(false);
    }

    public function tearDown() {
        Tracker_FormElementFactory::clearInstance();
        SystemEventManager::clearInstance();
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function itRetrievesAnEmptyArrayWhenBurndownIsInTheFutur()
    {
        $field = \Mockery::spy(\Tracker_FormElement_Field_Float::class);
        stub($this->form_element_factory)->getComputableFieldByNameForUser($this->sprint_tracker_id, 'remaining_effort', $this->current_user)->returns($field);

        $data = $this->field->getBurndownData($this->sprint, $this->current_user, time(), $this->duration);
        $this->assertEqual($data->getRemainingEffort(), array(0 => null));
    }

    public function itCreatesABurndownWithArtifactLinkedArtifactsAStartDateAndADuration()
    {
        $time_period    = new TimePeriodWithoutWeekEnd($this->timestamp, $this->duration);
        $burndown_data  = new Tracker_Chart_Data_Burndown($time_period);
        $this->field    = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->burndown_view = \Mockery::spy(\Tracker_Chart_BurndownView::class);

        stub($this->field)->getBurndown($burndown_data)->returns($this->burndown_view);
        stub($this->field)->userCanRead()->returns(true);
        stub($this->field)->getBurndownData($this->sprint, $this->current_user, $this->timestamp, $this->duration)->returns($burndown_data);
        stub($this->field)->getLogger()->returns(\Mockery::spy(\Tuleap\Tracker\FormElement\BurndownLogger::class));

        stub($this->form_element_factory)->getDateFieldByNameForUser(
            $this->sprint_tracker,
            $this->current_user,
            'start_date'
        )->returns(
            $this->start_date_field
        );

        stub($this->form_element_factory)->getNumericFieldByNameForUser(
            $this->sprint_tracker,
            $this->current_user,
            'duration'
        )->returns(
            $this->duration_field
        );

        $this->burndown_view->shouldReceive('display')->once();
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
        $form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'duration', $this->current_user)->returns($this->duration_field);
        Tracker_FormElementFactory::setInstance($form_element_factory);
    }

    public function itDisplaysAMessageWhenThereAreNoStartDateField() {
        $this->GivenSprintHasOneLinkedArtifact();

        $this->GivenFormElementFactoryHasOnlyDurationField();

        $this->expectException(new Tracker_FormElement_Chart_Field_Exception(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
        ));

        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }

   private function GivenFormElementFactoryHasOnlyStartDateField() {
        Tracker_FormElementFactory::clearInstance();
        $form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($form_element_factory)->getUsedFieldByNameForUser($this->sprint_tracker_id, 'start_date', $this->current_user)->returns($this->start_date_field);
        Tracker_FormElementFactory::setInstance($form_element_factory);
    }

    public function itDisplaysAMessageWhenThereAreNoDurationField() {
        $this->GivenSprintHasOneLinkedArtifact();

        $this->GivenFormElementFactoryHasOnlyStartDateField();

        $this->expectException(new Tracker_FormElement_Chart_Field_Exception(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
        ));

        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
    }

   public function itDisplaysAMessageWhenStartDateIsEmpty() {
        // Empty timestamp
        $start_date_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Date::class)->getTimestamp()->returns('');

        $sprint = \Mockery::spy(\Tracker_Artifact::class);
        stub($sprint)->getLinkedArtifacts()->returns($this->GivenOneLinkedArtifact());
        stub($sprint)->getValue($this->start_date_field)->returns($start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($this->duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);

        $this->expectException(new Tracker_FormElement_Chart_Field_Exception(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_start_date_warning')
        ));

        $this->field->fetchBurndownImage($sprint, $this->current_user);
    }

    public function itDisplaysAMessageWhenDurationIsEmpty() {
        // Empty duration
        $duration_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Integer::class)->getValue()->returns(0);

        $sprint = \Mockery::spy(\Tracker_Artifact::class);
        stub($sprint)->getLinkedArtifacts()->returns($this->GivenOneLinkedArtifact());
        stub($sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);

        $this->expectException(new Tracker_FormElement_Chart_Field_Exception(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning')
        ));

        $this->field->fetchBurndownImage($sprint, $this->current_user);
    }

    public function itDisplaysAMessageWhenDurationIsTooShort()
    {
        $duration_changeset_value = mockery_stub(\Tracker_Artifact_ChangesetValue_Integer::class)->getValue()->returns(1);

        $sprint = \Mockery::spy(\Tracker_Artifact::class);
        stub($sprint)->getLinkedArtifacts()->returns($this->GivenOneLinkedArtifact());
        stub($sprint)->getValue($this->start_date_field)->returns($this->start_date_changeset_value);
        stub($sprint)->getValue($this->duration_field)->returns($duration_changeset_value);
        stub($sprint)->getTracker()->returns($this->sprint_tracker);

        $this->expectException(
            new Tracker_FormElement_Chart_Field_Exception(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_duration_too_short')
            )
        );

        $this->field->fetchBurndownImage($sprint, $this->current_user);
    }

    public function itDisplaysAnErrorIfUserDoesntHaveThePermissionToAccessTheBurndownField() {
        $this->field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->field)->userCanRead($this->current_user)->returns(false);

        $this->expectException(new Tracker_FormElement_Chart_Field_Exception(
            $GLOBALS['Language']->getText('plugin_tracker', 'burndown_permission_denied')
        ));

        $this->field->fetchBurndownImage($this->sprint, $this->current_user);
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

        $this->field->getBurndownData($this->sprint, $this->current_user, $this->timestamp, $this->duration);

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
        $this->field->getBurndownData($this->sprint, $this->current_user, $this->timestamp, $this->duration);

        expect($this->event_manager)->createEvent()->never();
    }
}
