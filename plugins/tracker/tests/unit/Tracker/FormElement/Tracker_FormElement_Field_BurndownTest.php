<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Codendi_Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Psr\Log\LoggerInterface;
use SystemEventManager;
use Tracker_Artifact_ChangesetValue;
use Tracker_Chart_Data_Burndown;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElementFactory;
use TrackerManager;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_BurndownTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerManager
     */
    private $tracker_manager;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var Tracker_FormElement_Field_Burndown
     */
    private $burndown_field;

    /**
     * @var Artifact
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
    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $sprint;
    /**
     * @var int
     */
    private $sprint_tracker_id;
    /**
     * @var Mockery\MockInterface|\Tracker
     */
    private $sprint_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_manager = Mockery::mock(TrackerManager::class);

        $this->tracker    = \Mockery::spy(\Tracker::class);
        $this->tracker_id = 101;
        $this->tracker->shouldReceive('getId')->andReturn($this->tracker_id);

        $this->artifact = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->form_element_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $this->burndown_field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $logger = \Mockery::spy(LoggerInterface::class);
        $this->burndown_field->shouldReceive('getLogger')->andReturn($logger);
        $this->burndown_field->shouldReceive('fetchBurndownReadOnly')
            ->with($this->artifact)
            ->andReturn('<div id="burndown-chart"></div>');
        $this->burndown_field->shouldReceive('isCacheBurndownAlreadyAsked')->andReturnFalse();
        $this->burndown_field->shouldReceive('getTimeframeCalculator')
            ->andReturn(Mockery::mock(IComputeTimeframes::class));

        $this->user = \Mockery::spy(\PFUser::class);

        SystemEventManager::setInstance(\Mockery::spy(SystemEventManager::class));

        $this->sprint            = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->sprint_tracker_id = 113;
        $this->sprint_tracker    = \Mockery::spy(\Tracker::class);
        $this->sprint_tracker->shouldReceive("getId")->andReturn($this->sprint_tracker_id);
        $this->sprint->shouldReceive("getTracker")->andReturn($this->sprint_tracker);
    }

    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();
        parent::tearDown();
    }

    private function getAStartDateField($value)
    {
        $start_date_field           = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $start_date_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset_value->shouldReceive('getTimestamp')->andReturn($value);

        $this->artifact->shouldReceive('getValue')
            ->with($start_date_field)
            ->andReturn($start_date_changeset_value);

        $this->form_element_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )->andReturn(
                $start_date_field
            );

        $start_date_field->shouldReceive('userCanRead')->andReturnTrue();
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')
            ->with('start_date', ['date'])
            ->andReturnTrue();
    }

    private function getADurationField($value)
    {
        $duration_field = Mockery::spy(\Tracker_FormElement_Field_Integer::class);

        $duration_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $duration_changeset_value->shouldReceive('getValue')->andReturn($value);

        $this->artifact->shouldReceive('getValue')
            ->with($duration_field)
            ->andReturn($duration_changeset_value);

        $this->form_element_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'duration'
            )->andReturn(
                $duration_field
            );

        $duration_field->shouldReceive('userCanRead')->andReturnTrue();
        $this->tracker->shouldReceive('hasFormElementWithNameAndType')
            ->with('duration', ['int', 'float', 'computed'])
            ->andReturnTrue();
    }

    public function testItRendersAD3BurndownMontPointWhenBurndownHasAStartDateAndADuration()
    {
        $this->user->shouldReceive('isAdmin')->andReturnTrue();
        $this->burndown_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);

        $this->assertEquals(
            '<div id="burndown-chart"></div>',
            $result
        );
    }

    public function testItRendersAJPGraphBurndownErrorWhenUserCantReadBurndownField()
    {
        $this->burndown_field->shouldReceive("getCurrentUser")->andReturn($this->user);
        $this->burndown_field->shouldReceive("userCanRead")->andReturn(false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage('You are not allowed to access this field.');

        $this->burndown_field->fetchBurndownImage($this->artifact, $this->user);
    }

    public function testButtonForceCacheGenerationIsNotPresentWhenStartDateIsNotSet()
    {
        $this->user->shouldReceive('isAdmin')->andReturnTrue();
        $this->burndown_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $timestamp = null;
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $this->burndown_field->shouldReceive('renderPresenter')->andReturn('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);

        $this->assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testButtonForceCacheGenerationIsNotRenderedWhenDurationIsNotSet()
    {
        $this->user->shouldReceive('isAdmin')->andReturnTrue();
        $this->burndown_field->shouldReceive('getCurrentUser')->andReturn($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = null;
        $this->getADurationField($duration);

        $this->burndown_field->shouldReceive('renderPresenter')->andReturn('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact, $this->changesetValue);

        $this->assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testItDisplaysTheOldJPGraph()
    {
        $timestamp = mktime(0, 0, 0, 7, 3, 2011);
        $duration  = 5;

        $date_period   = DatePeriodWithoutWeekEnd::buildFromDuration($timestamp, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period);

        $burndown_view = \Mockery::spy(\Tracker_Chart_BurndownView::class);

        $this->burndown_field->shouldReceive('getBurndown')
            ->with($burndown_data)
            ->andReturn($burndown_view);

        $this->burndown_field->shouldReceive('userCanRead')->andReturnTrue();

        $this->burndown_field->shouldReceive('buildBurndownDataForLegacy')
            ->with($this->user, $this->sprint)
            ->andReturn($burndown_data);

        $this->burndown_field->shouldReceive('getLogger')->andReturn(\Mockery::spy(Burndown\Psr\Log\LoggerInterface::class));

        $start_date_field = Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $this->form_element_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->sprint_tracker,
                $this->user,
                'start_date'
            )->andReturn(
                $start_date_field
            );

        $duration_field = Mockery::spy(\Tracker_FormElement_Field_Integer::class);
        $this->form_element_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with(
                $this->sprint_tracker,
                $this->user,
                'duration'
            )->andReturn(
                $duration_field
            );

        $burndown_view->shouldReceive('display')->once();

        $this->burndown_field->fetchBurndownImage($this->sprint, $this->user);
    }

    public function testProcessShouldRenderGraphWhenShowBurndownFuncIsCalled(): void
    {
        $artifact_id = 999;

        $request = new Codendi_Request(
            [
                'formElement' => 1234,
                'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
                'src_aid'     => $artifact_id,
            ]
        );

        $artifact        = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifactFactory = Mockery::mock(\Tracker_ArtifactFactory::class);
        $artifactFactory->shouldReceive('getArtifactById')->withArgs([$artifact_id])->andReturn($artifact);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getArtifactFactory')->andReturn($artifactFactory);
        $field->shouldReceive('fetchBurndownImage')->with($artifact, $this->user)->once();

        $field->process($this->tracker_manager, $request, $this->user);
    }

    public function testProcessMustNotBuildBurndownWhenSrcAidIsNotValid(): void
    {
        $request = new Codendi_Request(['formElement' => 1234,
            'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
            'src_aid'     => '; DROP DATABASE mouuahahahaha!',
        ]);



        $artifactFactory = Mockery::mock(\Tracker_ArtifactFactory::class);
        $artifactFactory->shouldReceive('getArtifactById')->andReturn(null);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getArtifactFactory')->andReturn($artifactFactory);
        $field->shouldReceive('fetchBurndownImage')->never();

        $field->process($this->tracker_manager, $request, $this->user);
    }

    public function testProcessMustNotBuildBurndownWhenArtifactDoesNotExist(): void
    {
        $request = new Codendi_Request(['formElement' => 1234,
            'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
            'src_aid'     => 999,
        ]);

        $artifactFactory = Mockery::mock(\Tracker_ArtifactFactory::class);
        $artifactFactory->shouldReceive('getArtifactById')->andReturn(null);

        $field = \Mockery::mock(\Tracker_FormElement_Field_Burndown::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getArtifactFactory')->andReturn($artifactFactory);
        $field->shouldReceive('fetchBurndownImage')->never();

        $field->process($this->tracker_manager, $request, $this->user);
    }
}
