<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Psr\Log\NullLogger;
use Tracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\SemanticTimeframeWithDurationRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeframeWithDurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TimeframeWithDuration $timeframe;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Date
     */
    private $start_date_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Integer
     */
    private $duration_field;
    private \PFUser $user;
    private Tracker $tracker;

    protected function setUp(): void
    {
        $this->start_date_field = $this->getMockedDateField(1001);
        $this->duration_field   = $this->getMockedDurationField(1002);

        $this->tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();

        $this->timeframe = new TimeframeWithDuration(
            $this->start_date_field,
            $this->duration_field
        );
    }

    /**
     * @testWith [1001, true]
     *           [1002, true]
     *           [1003, false]
     */
    public function testItReturnsTrueWhenFieldIsUsed(int $field_id, bool $is_used): void
    {
        $field = $this->getMockedDateField($field_id);

        $this->assertEquals(
            $is_used,
            $this->timeframe->isFieldUsed($field)
        );
    }

    public function testItReturnsItsConfigDescription(): void
    {
        $this->start_date_field->expects(self::any())->method('getLabel')->will(self::returnValue('Start date'));
        $this->duration_field->expects(self::any())->method('getLabel')->will(self::returnValue('Duration'));

        $this->assertEquals(
            'Timeframe is based on start date field "Start date" and duration field "Duration".',
            $this->timeframe->getConfigDescription()
        );
    }

    public function testItIsDefined(): void
    {
        $this->assertTrue($this->timeframe->isDefined());
    }

    public function testItDoesNotExportToXMLIfStartDateIsNotInFieldMapping(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, []);

        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToXMLIfDurationIsNotInFieldMapping(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testItExportsToXML(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
            'F102' => 1002,
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('F101', (string) $root->semantic->start_date_field['REF']);
        $this->assertEquals('F102', (string) $root->semantic->duration_field['REF']);
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     */
    public function testItDoesNotExportToRESTWhenUserCanReadFields(bool $can_read_start_date, bool $can_read_duration): void
    {
        $this->start_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue($can_read_start_date));
        $this->duration_field->expects(self::any())->method('userCanRead')->will(self::returnValue($can_read_duration));

        $this->assertNull($this->timeframe->exportToREST($this->user));
    }

    public function testItExportsToREST(): void
    {
        $this->start_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::any())->method('userCanRead')->will(self::returnValue(true));

        $this->assertEquals(
            new SemanticTimeframeWithDurationRepresentation(
                1001,
                1002
            ),
            $this->timeframe->exportToREST($this->user)
        );
    }

    public function testItSaves(): void
    {
        $dao     = $this->getMockBuilder(SemanticTimeframeDao::class)->disableOriginalConstructor()->getMock();
        $tracker = $this->getMockBuilder(\Tracker::class)->disableOriginalConstructor()->getMock();

        $dao->expects(self::once())->method('save')->with(113, 1001, 1002, null, null)->will(self::returnValue(true));
        $tracker->expects(self::once())->method('getId')->will(self::returnValue(113));

        self::assertTrue(
            $this->timeframe->save($tracker, $dao)
        );
    }

    public function testItBuildADatePeriodWithoutWeekObjectForArtifactForREST(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifact($start_date, $duration);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $date_period->getEndDate());
        $this->assertSame(10, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodWithoutWeekObjectForRESTWithStartDateAsNullForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration = 10;

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutStartDate($duration);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertNull($date_period->getStartDate());
        $this->assertNull($date_period->getEndDate());
        $this->assertSame(10, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodForRESTWithNullDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutDuration($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        self::assertNull($date_period->getEndDate());
        self::assertNull($date_period->getDuration());
    }

    public function testItBuildsDatePeriodWithoutWeekendsForArtifacts(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifact($start_date, $duration);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $date_period->getEndDate());
        $this->assertSame(10, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration = 10;

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutStartDate($duration);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(0, $date_period->getStartDate());
        $this->assertNull($date_period->getEndDate());
        $this->assertSame(10, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodWithZeroDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutDuration($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(strtotime($start_date), $date_period->getEndDate());
        $this->assertSame(0, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodForChartWhenStartDateAndDurationAreSet(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifact($start_date, $duration);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $date_period->getEndDate());
        $this->assertSame(10, $date_period->getDuration());
    }

    public function testItThrowsAnExceptionWhenStartDateIsEmptyOrHasNoValueInChartContext(): void
    {
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutStartDate(1);

        self::expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );
    }

    /**
     * @testWith [-1]
     *           [0]
     *           [1]
     *           [null]
     */
    public function testItThrowsAnExceptionWhenDurationHasParticularValuesInChartContext(?int $duration): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        if ($duration) {
            $artifact = $this->anArtifact($start_date, $duration);
        } else {
            $artifact = $this->anArtifactWithoutDuration($start_date);
        }

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );
    }

    public function testItThrowsAnExceptionWhenDurationHasNoLastChangesetValueInChartContext(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutDuration($start_date);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );
    }

    public function testItReturnsTrueWhenUserCanReadFields(): void
    {
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        self::assertTrue($this->timeframe->userCanReadTimeframeFields($this->user));
    }

    public function testItReturnsFalseWhenUserCannotReadFields(): void
    {
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(false));
        // duration_field->userCanRead is not called cause of && operator

        self::assertFalse($this->timeframe->userCanReadTimeframeFields($this->user));
    }

    public function testItReturnsTrueWhenAllFieldsAreZero(): void
    {
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutAnyValue();

        self::assertTrue($this->timeframe->isAllSetToZero(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        ));
    }

    public function testItReturnsFalseWhenAtLeastOneFieldIsNotZero(): void
    {
        $this->start_date_field->expects(self::exactly(3))->method('userCanRead')->will(self::returnValue(true));
        $this->duration_field->expects(self::exactly(3))->method('userCanRead')->will(self::returnValue(true));

        $start_date = '07/01/2013';
        $duration   = 10;
        $artifact1  = $this->anArtifact($start_date, $duration);
        $artifact2  = $this->anArtifactWithoutDuration($start_date);
        $artifact3  = $this->anArtifactWithoutStartDate($duration);

        self::assertFalse($this->timeframe->isAllSetToZero(
            $artifact1->getLastChangeset(),
            $this->user,
            new NullLogger()
        ));
        self::assertFalse($this->timeframe->isAllSetToZero(
            $artifact2->getLastChangeset(),
            $this->user,
            new NullLogger()
        ));
        self::assertFalse($this->timeframe->isAllSetToZero(
            $artifact3->getLastChangeset(),
            $this->user,
            new NullLogger()
        ));
    }

    private function getMockedDateField(int $field_id): \Tracker_FormElement_Field_Date
    {
        $mock = $this->getMockBuilder(\Tracker_FormElement_Field_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects(self::any())->method('getId')->will(self::returnValue($field_id));

        return $mock;
    }

    private function getMockedDurationField(int $field_id)
    {
        $mock = $this->getMockBuilder(\Tracker_FormElement_Field_Integer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects(self::any())->method('getId')->will(self::returnValue($field_id));

        return $mock;
    }

    private function anArtifact(string $start_date, int $duration): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset->setFieldValue(
            $this->start_date_field,
            ChangesetValueDateTestBuilder::aValue(1, $changeset, $this->start_date_field)
                ->withTimestamp(strtotime($start_date))
                ->build()
        );
        $changeset->setFieldValue(
            $this->duration_field,
            ChangesetValueIntegerTestBuilder::aValue(2, $changeset, $this->duration_field)
                ->withValue($duration)
                ->build()
        );

        return ArtifactTestBuilder::anArtifact('1')
            ->withTitle('title')
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }

    private function anArtifactWithoutStartDate(int $duration): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset->setFieldValue($this->start_date_field, null);
        $changeset->setFieldValue(
            $this->duration_field,
            ChangesetValueIntegerTestBuilder::aValue(2, $changeset, $this->duration_field)
                ->withValue($duration)
                ->build()
        );

        return ArtifactTestBuilder::anArtifact('1')
            ->withTitle('title')
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }

    private function anArtifactWithoutDuration(string $start_date): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset->setFieldValue(
            $this->start_date_field,
            ChangesetValueDateTestBuilder::aValue(1, $changeset, $this->start_date_field)
                ->withTimestamp(strtotime($start_date))
                ->build()
        );
        $changeset->setFieldValue($this->duration_field, null);

        return ArtifactTestBuilder::anArtifact('1')
            ->withTitle('title')
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }

    private function anArtifactWithoutAnyValue(): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset->setFieldValue($this->start_date_field, null);
        $changeset->setFieldValue($this->duration_field, null);

        return ArtifactTestBuilder::anArtifact('1')
            ->withTitle('title')
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }
}
