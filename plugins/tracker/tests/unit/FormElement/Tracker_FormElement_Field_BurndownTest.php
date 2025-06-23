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
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SystemEventManager;
use Tracker_ArtifactFactory;
use Tracker_Chart_BurndownView;
use Tracker_Chart_Data_Burndown;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Burndown;
use Tracker_FormElementFactory;
use TrackerManager;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_BurndownTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private TrackerManager&MockObject $tracker_manager;
    private Tracker $tracker;
    private Tracker_FormElement_Field_Burndown&MockObject $burndown_field;
    private Artifact $artifact;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private PFUser $user;
    private Artifact $sprint;
    private Tracker $sprint_tracker;

    protected function setUp(): void
    {
        $this->tracker_manager = $this->createMock(TrackerManager::class);

        $project = ProjectTestBuilder::aProject()->build();

        $tracker_id    = 101;
        $this->tracker = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject($project)->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(123)->inTracker($this->tracker)
            ->withChangesets(ChangesetTestBuilder::aChangeset(65412)->build())
            ->build();

        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->form_element_factory);

        $this->burndown_field = $this->createPartialMock(Tracker_FormElement_Field_Burndown::class, [
            'getLogger', 'fetchBurndownReadOnly', 'getTimeframeCalculator', 'getCurrentUser', 'userCanRead', 'renderPresenter', 'getBurndown', 'buildBurndownDataForLegacy',
        ]);

        $this->burndown_field->method('getLogger')->willReturn(new NullLogger());
        $this->burndown_field->method('fetchBurndownReadOnly')->with($this->artifact)->willReturn('<div id="burndown-chart"></div>');
        $this->burndown_field->method('getTimeframeCalculator')->willReturn($this->createStub(IComputeTimeframes::class));

        $this->user = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $system_event_manager = $this->createMock(SystemEventManager::class);
        SystemEventManager::setInstance($system_event_manager);
        $system_event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturn(true);

        $sprint_tracker_id    = 113;
        $this->sprint_tracker = TrackerTestBuilder::aTracker()->withId($sprint_tracker_id)->withProject($project)->build();
        $this->sprint         = ArtifactTestBuilder::anArtifact(456)->inTracker($this->sprint_tracker)->build();
    }

    protected function tearDown(): void
    {
        SystemEventManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();
    }

    private function getAStartDateField(?int $value): void
    {
        $start_date_field = DateFieldBuilder::aDateField(789)->withReadPermission($this->user, true)->build();
        if ($value !== null) {
            $start_date_changeset_value = ChangesetValueDateTestBuilder::aValue(1, $this->artifact->getLastChangeset(), $start_date_field)
                ->withTimestamp($value)
                ->build();
        } else {
            $start_date_changeset_value = null;
        }
        $this->artifact->getLastChangeset()->setFieldValue($start_date_field, $start_date_changeset_value);

        $this->form_element_factory->method('getUsedFieldByName')->with('start_date', ['date'])->willReturn($start_date_field);
    }

    private function getADurationField(?int $value): void
    {
        $duration_field = IntFieldBuilder::anIntField(790)->withReadPermission($this->user, true)->build();
        if ($value !== null) {
            $duration_changeset_value = ChangesetValueIntegerTestBuilder::aValue(2, $this->artifact->getLastChangeset(), $duration_field)
                ->withValue($value)
                ->build();
        } else {
            $duration_changeset_value = null;
        }
        $this->artifact->getLastChangeset()->setFieldValue($duration_field, $duration_changeset_value);

        $this->form_element_factory->method('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')->willReturn($duration_field);

        $this->form_element_factory->method('getUsedFieldByName')->with('duration', ['int', 'float', 'computed'])->willReturn($duration_field);
    }

    public function testItRendersAD3BurndownMontPointWhenBurndownHasAStartDateAndADuration(): void
    {
        $this->burndown_field->method('getCurrentUser')->willReturn($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact);

        self::assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testItRendersAJPGraphBurndownErrorWhenUserCantReadBurndownField(): void
    {
        $this->burndown_field->method('getCurrentUser')->willReturn($this->user);
        $this->burndown_field->method('userCanRead')->willReturn(false);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage('You are not allowed to access this field.');

        $this->burndown_field->fetchBurndownImage($this->artifact, $this->user);
    }

    public function testButtonForceCacheGenerationIsNotPresentWhenStartDateIsNotSet(): void
    {
        $this->burndown_field->method('getCurrentUser')->willReturn($this->user);

        $timestamp = null;
        $this->getAStartDateField($timestamp);

        $duration = 5;
        $this->getADurationField($duration);

        $this->burndown_field->method('renderPresenter')->willReturn('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact);

        self::assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testButtonForceCacheGenerationIsNotRenderedWhenDurationIsNotSet(): void
    {
        $this->burndown_field->method('getCurrentUser')->willReturn($this->user);

        $timestamp = mktime(0, 0, 0, 20, 12, 2016);
        $this->getAStartDateField($timestamp);

        $duration = null;
        $this->getADurationField($duration);

        $this->burndown_field->method('renderPresenter')->willReturn('<div id="burndown-chart"></div>');

        $result = $this->burndown_field->fetchArtifactValueReadOnly($this->artifact);

        self::assertSame('<div id="burndown-chart"></div>', $result);
    }

    public function testItDisplaysTheOldJPGraph(): void
    {
        $timestamp = mktime(0, 0, 0, 7, 3, 2011);
        $duration  = 5;

        $date_period   = DatePeriodWithOpenDays::buildFromDuration($timestamp, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period);

        $burndown_view = $this->createMock(Tracker_Chart_BurndownView::class);

        $this->burndown_field->method('getBurndown')->with($burndown_data)->willReturn($burndown_view);

        $this->burndown_field->method('userCanRead')->willReturn(true);

        $this->burndown_field->method('buildBurndownDataForLegacy')->with($this->user, $this->sprint)->willReturn($burndown_data);

        $duration_field = IntFieldBuilder::anIntField(452)->build();
        $this->form_element_factory->method('getNumericFieldByNameForUser')
            ->with($this->sprint_tracker, $this->user, 'duration')->willReturn($duration_field);

        $burndown_view->expects($this->once())->method('display');

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

        $artifact        = ArtifactTestBuilder::anArtifact($artifact_id)->build();
        $artifactFactory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifactFactory->method('getArtifactById')->with($artifact_id)->willReturn($artifact);

        $field = $this->createPartialMock(Tracker_FormElement_Field_Burndown::class, ['getArtifactFactory', 'fetchBurndownImage']);
        $field->method('getArtifactFactory')->willReturn($artifactFactory);
        $field->expects($this->once())->method('fetchBurndownImage')->with($artifact, $this->user);

        $field->process($this->tracker_manager, $request, $this->user);
    }

    public function testProcessMustNotBuildBurndownWhenSrcAidIsNotValid(): void
    {
        $request = new Codendi_Request([
            'formElement' => 1234,
            'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
            'src_aid'     => '; DROP DATABASE mouuahahahaha!',
        ]);


        $artifactFactory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifactFactory->method('getArtifactById')->willReturn(null);

        $field = $this->createPartialMock(Tracker_FormElement_Field_Burndown::class, ['getArtifactFactory', 'fetchBurndownImage']);
        $field->method('getArtifactFactory')->willReturn($artifactFactory);
        $field->expects($this->never())->method('fetchBurndownImage');

        $field->process($this->tracker_manager, $request, $this->user);
    }

    public function testProcessMustNotBuildBurndownWhenArtifactDoesNotExist(): void
    {
        $this->expectNotToPerformAssertions();
        $request = new Codendi_Request([
            'formElement' => 1234,
            'func'        => Tracker_FormElement_Field_Burndown::FUNC_SHOW_BURNDOWN,
            'src_aid'     => 999,
        ]);

        $artifactFactory = $this->createMock(Tracker_ArtifactFactory::class);
        $artifactFactory->method('getArtifactById')->willReturn(null);

        $field = $this->createPartialMock(Tracker_FormElement_Field_Burndown::class, ['getArtifactFactory', 'fetchBurndownImage']);
        $field->method('getArtifactFactory')->willReturn($artifactFactory);

        $field->process($this->tracker_manager, $request, $this->user);
    }
}
