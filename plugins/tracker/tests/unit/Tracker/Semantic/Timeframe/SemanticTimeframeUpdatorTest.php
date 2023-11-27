<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotificationStub;

class SemanticTimeframeUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    /**
     * @var SemanticTimeframeDao
     */
    private $semantic_timeframe_dao;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var \Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var int
     */
    private $tracker_id;
    private SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever&MockObject $suitable_trackers_retriever;

    protected function setUp(): void
    {
        $this->semantic_timeframe_dao      = $this->createMock(SemanticTimeframeDao::class);
        $this->tracker                     = $this->createMock(\Tracker::class);
        $this->formelement_factory         = $this->createMock(\Tracker_FormElementFactory::class);
        $this->suitable_trackers_retriever = $this->createMock(SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever::class);

        $this->tracker_id = 123;
        $this->tracker->method('getId')->willReturn($this->tracker_id);
    }

    public function testItDoesNotUpdateIfAFieldIdIsNotNumeric(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => 'start',
            'duration-field-id'   => '1234',
        ]);

        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method("save");

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItDoesNotUpdateIfStartDateFieldIdIsMissing(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => '',
            'duration-field-id'   => '5678',
        ]);

        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method("save");

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItDoesNotUpdateIfDurationFieldIdIsMissing(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => '1234',
            'duration-field-id'   => '',
        ]);

        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method("save");

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItDoesNotUpdateIfAFieldCannotBeFoundInTheTracker(): void
    {
        $request = new \Codendi_Request([
            'start-date-field-id' => '1234',
            'duration-field-id'   => '5678',
        ]);

        $this->formelement_factory->method("getUsedDateFieldById")->with($this->tracker, 1234)->willReturn(null);
        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method("save");

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'An error occurred while updating the timeframe semantic'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItUpdatesTheSemanticWithDuration(): void
    {
        $start_date_field_id = 1234;
        $duration_field_id   = 5678;
        $request             = new \Codendi_Request([
            'start-date-field-id' => $start_date_field_id,
            'duration-field-id'   => $duration_field_id,
        ]);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = $this->createMock(\Tracker_FormElement_Field_Integer::class);

        $start_date_field->method('getTrackerId')->willReturn($this->tracker_id);
        $duration_field->method('getTrackerId')->willReturn($this->tracker_id);

        $this->formelement_factory->method("getUsedDateFieldById")->with($this->tracker, $start_date_field_id)->willReturn($start_date_field);
        $this->formelement_factory->method("getUsedFieldByIdAndType")->with(
            $this->tracker,
            $duration_field_id,
            ['int', 'float', 'computed']
        )->willReturn($duration_field);

        $this->semantic_timeframe_dao
            ->expects(self::once())
            ->method("save")->with(
                $this->tracker_id,
                $start_date_field_id,
                $duration_field_id,
                null,
                null
            )->willReturn(true);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe updated successfully'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItUpdatesTheSemanticWithEndDate(): void
    {
        $start_date_field_id = 1234;
        $end_date_field_id   = 5678;
        $request             = new \Codendi_Request([
            'start-date-field-id' => $start_date_field_id,
            'end-date-field-id'   => $end_date_field_id,
        ]);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field   = $this->createMock(\Tracker_FormElement_Field_Date::class);

        $start_date_field->method('getTrackerId')->willReturn($this->tracker_id);
        $end_date_field->method('getTrackerId')->willReturn($this->tracker_id);

        $this->formelement_factory
            ->method("getUsedDateFieldById")
            ->willReturnCallback(
                static fn ($tracker, $field_id) => match ($field_id) {
                    $start_date_field_id => $start_date_field,
                    $end_date_field_id => $end_date_field,
                }
            );

        $this->semantic_timeframe_dao
            ->expects(self::once())
            ->method("save")->with(
                $this->tracker_id,
                $start_date_field_id,
                null,
                $end_date_field_id,
                null
            )->willReturn(true);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe updated successfully'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItUpdatesTheSemanticWhenItIsImpliedFromAnotherTracker(): void
    {
        $sprints_tracker_id = 150;
        $request            = new \Codendi_Request(
            [
                'implied-from-tracker-id' => $sprints_tracker_id,
            ]
        );

        $this->suitable_trackers_retriever->method('getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom')
            ->with($this->tracker)
            ->willReturn([
                '150' => $this->createMock(\Tracker::class),
            ]);

        $this->semantic_timeframe_dao
            ->expects(self::once())
            ->method("save")->with(
                $this->tracker_id,
                null,
                null,
                null,
                $sprints_tracker_id
            )->willReturn(true);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe updated successfully'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItRejectsTheSemanticWhenItIsImpliedFromAnotherTrackerAndCalendarEventsAreUsed(): void
    {
        $sprints_tracker_id = 150;
        $request            = new \Codendi_Request(
            [
                'implied-from-tracker-id' => $sprints_tracker_id,
            ]
        );

        $this->suitable_trackers_retriever->method('getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom')
            ->with($this->tracker)
            ->willReturn([
                '150' => $this->createMock(\Tracker::class),
            ]);

        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method("save");

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'An error occurred while updating the timeframe semantic')
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItRejectsIfBothDurationAndEndDateAreSent(): void
    {
        $start_date_field_id = 1234;
        $end_date_field_id   = 5678;
        $duration_field_id   = 345;

        $request = new \Codendi_Request([
            'start-date-field-id' => $start_date_field_id,
            'duration-field-id'   => $duration_field_id,
            'end-date-field-id'   => $end_date_field_id,
        ]);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field   = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = $this->createMock(\Tracker_FormElement_Field_Numeric::class);

        $start_date_field->method('getTrackerId')->willReturn($this->tracker_id);
        $end_date_field->method('getTrackerId')->willReturn($this->tracker_id);
        $duration_field->method('getTrackerId')->willReturn($this->tracker_id);

        $this->formelement_factory
            ->method("getUsedDateFieldById")
            ->willReturnCallback(
                static fn ($tracker, $field_id) => match ($field_id) {
                    $start_date_field_id => $start_date_field,
                    $end_date_field_id => $end_date_field,
                }
            );
        $this->formelement_factory->method("getUsedFieldByIdAndType")->with(
            $this->tracker,
            $duration_field_id,
            ['int', 'float', 'computed']
        )->willReturn($duration_field);

        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method("save");

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            dgettext('tuleap-tracker', 'An error occurred while updating the timeframe semantic')
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->update($this->tracker, $request);
    }

    public function testItDoesNotResetWhenSomeTimeframeConfigurationsRelyOnTheCurrentOne(): void
    {
        $sprint_tracker_id = 106;
        $sprint_tracker    = TrackerTestBuilder::aTracker()->withId($sprint_tracker_id)->build();

        $this->semantic_timeframe_dao
            ->expects(self::once())
            ->method('getSemanticsImpliedFromGivenTracker')
            ->with($sprint_tracker_id)
            ->willReturn([
                [
                    'tracker_id' => 104,
                    'implied_from_tracker_id' => $sprint_tracker_id,
                ],
            ]);

        $this->semantic_timeframe_dao
            ->expects(self::never())
            ->method('deleteTimeframeSemantic');

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::ERROR,
            'You cannot reset this semantic because some trackers inherit their own semantic timeframe from this one.'
        );

        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->reset($sprint_tracker);
    }

    /**
     * @testWith [null]
     *           [[]]
     */
    public function testItResetsTheTimeframeSemanticOfAGivenTracker(?array $implied_semantics): void
    {
        $sprint_tracker_id = 106;
        $sprint_tracker    = TrackerTestBuilder::aTracker()->withId($sprint_tracker_id)->build();

        $this->semantic_timeframe_dao
            ->expects(self::once())
            ->method('getSemanticsImpliedFromGivenTracker')
            ->with($sprint_tracker_id)
            ->willReturn($implied_semantics);

        $this->semantic_timeframe_dao
            ->expects(self::once())
            ->method('deleteTimeframeSemantic')
            ->with($sprint_tracker_id);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(
            \Feedback::INFO,
            'Semantic timeframe reset successfully'
        );
        $updator = new SemanticTimeframeUpdator(
            $this->semantic_timeframe_dao,
            $this->formelement_factory,
            $this->suitable_trackers_retriever,
            CheckEventShouldBeSentInNotificationStub::withoutEventInNotification(),
        );
        $updator->reset($sprint_tracker);
    }
}
