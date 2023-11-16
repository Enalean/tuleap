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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\SemanticTimeframeWithEndDateRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeframeWithEndDateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TimeframeWithEndDate $timeframe;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Date
     */
    private $start_date_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Date
     */
    private $end_date_field;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->start_date_field = $this->getMockedDateField(1001);
        $this->end_date_field   = $this->getMockedDateField(1003);

        $this->user = UserTestBuilder::anActiveUser()->build();

        $this->timeframe = new TimeframeWithEndDate(
            $this->start_date_field,
            $this->end_date_field
        );
    }

    /**
     * @testWith [1001, true]
     *           [1002, false]
     *           [1003, true]
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
        $this->start_date_field->expects($this->any())->method('getLabel')->will(self::returnValue('Start date'));
        $this->end_date_field->expects($this->any())->method('getLabel')->will(self::returnValue('End date'));

        $this->assertEquals(
            'Timeframe is based on start date field "Start date" and end date field "End date".',
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

    public function testItDoesNotExportToXMLIfEndDateIsNotInFieldMapping(): void
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
            'F102' => 1003,
        ]);

        $this->assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('F101', (string) $root->semantic->start_date_field['REF']);
        $this->assertEquals('F102', (string) $root->semantic->end_date_field['REF']);
    }

    /**
     * @testWith [false, false]
     *           [true, false]
     */
    public function testItDoesNotExportToRESTWhenUserCanReadFields(bool $can_read_start_date, bool $can_read_end_date): void
    {
        $this->start_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue($can_read_start_date));
        $this->end_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue($can_read_end_date));

        $this->assertNull($this->timeframe->exportToREST($this->user));
    }

    public function testItExportsToREST(): void
    {
        $this->start_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::any())->method('userCanRead')->will(self::returnValue(true));

        $this->assertEquals(
            new SemanticTimeframeWithEndDateRepresentation(
                1001,
                1003
            ),
            $this->timeframe->exportToREST($this->user)
        );
    }

    public function testItSaves(): void
    {
        $dao     = $this->getMockBuilder(SemanticTimeframeDao::class)->disableOriginalConstructor()->getMock();
        $tracker = TrackerTestBuilder::aTracker()->withId(113)->build();

        $dao->expects(self::once())->method('save')->with(113, 1001, null, 1003, null)->will(self::returnValue(true));

        self::assertTrue(
            $this->timeframe->save($tracker, $dao)
        );
    }

    public function testItBuildsADatePeriodWithNoEndDateWhenNoEndDateValueExist(): void
    {
        $start_date = '01/20/2021';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        self::assertSame(strtotime($start_date), $date_period->getStartDate());
        self::assertNull($date_period->getEndDate());
        self::assertSame(null, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodForRESTFromEndDate(): void
    {
        $start_date = '07/01/2013';
        $end_date   = '07/03/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifact($start_date, $end_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(strtotime($end_date), $date_period->getEndDate());
        $this->assertEquals(2, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodForRESTFromEndDateWithNullIfEndDateIsNotReadable(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(false));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertNull($date_period->getEndDate());
        $this->assertNull($date_period->getDuration());
    }

    public function testItBuildsADatePeriodForRESTFromEndDateWithNullIfEndDateHasNoValue(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertNull($date_period->getEndDate());
        $this->assertNull($date_period->getDuration());
    }

    public function testItBuildsADatePeriodWithEndDateForArtifact(): void
    {
        $start_date = '07/01/2013';
        $end_date   = '07/03/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifact($start_date, $end_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        self::assertSame(strtotime($start_date), $date_period->getStartDate());
        self::assertSame(strtotime($end_date), $date_period->getEndDate());
        self::assertSame(2, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodWithEndDateForArtifactWithZeroForEndDateIfUserCannotRead(): void
    {
        $start_date = '07/01/2013';
        $logger     = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        $logger->expects(self::once())->method('warning');
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(false));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $this->user,
            $logger
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(0, $date_period->getEndDate());
        // duration between start date (07/01/2013) and 01/01/1970 since user cannot read the field.
        // Weird but consistent with date field/duration behavior.
        $this->assertSame(-11347, $date_period->getDuration());
    }

    public function testItBuildsADatePeriodWithEndDateForArtifactWithZeroForEndDateIfNoLastChangesetValue(): void
    {
        $start_date = '07/01/2013';
        $logger     = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        $logger->expects(self::once())->method('warning');
        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $this->user,
            $logger
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(0, $date_period->getEndDate());
        // duration between start date (07/01/2013) and 01/01/1970 since user cannot read the field.
        // Weird but consistent with date field/duration behavior.
        $this->assertSame(-11347, $date_period->getDuration());
    }

    public function testItThrowsAnExceptionWhenEndDateHasNoLastChangesetValueInChartContext(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );
    }

    public function testItThrowsAnExceptionWhenEndDateIsNotReadableInChartContext(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(false));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );
    }

    public function testItThrowsAnExceptionWhenEndDateIsEmpty(): void
    {
        $start_date = '07/01/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifactWithoutEndDate($start_date);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );
    }

    public function testItReturnsTimeframeFromEndDateInChartContext(): void
    {
        $start_date = '07/01/2013';
        $end_date   = '07/15/2013';

        $this->start_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));
        $this->end_date_field->expects(self::once())->method('userCanRead')->will(self::returnValue(true));

        $artifact = $this->anArtifact($start_date, $end_date);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $this->user,
            new NullLogger()
        );

        $this->assertSame(strtotime($start_date), $date_period->getStartDate());
        $this->assertSame(strtotime($end_date), $date_period->getEndDate());
        $this->assertSame(10, $date_period->getDuration());
    }

    private function getMockedDateField(int $field_id): \Tracker_FormElement_Field_Date
    {
        $mock = $this->getMockBuilder(\Tracker_FormElement_Field_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())->method('getId')->will($this->returnValue($field_id));

        return $mock;
    }

    private function anArtifact(string $start_date, string $end_date): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset->setFieldValue(
            $this->start_date_field,
            ChangesetValueDateTestBuilder::aValue(1, $changeset, $this->start_date_field)
                ->withTimestamp(strtotime($start_date))
                ->build()
        );
        $changeset->setFieldValue(
            $this->end_date_field,
            ChangesetValueDateTestBuilder::aValue(2, $changeset, $this->end_date_field)
                ->withTimestamp(strtotime($end_date))
                ->build()
        );

        return ArtifactTestBuilder::anArtifact('4')
            ->withTitle('title')
            ->inTracker(TrackerTestBuilder::aTracker()->build())
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }

    private function anArtifactWithoutEndDate(string $start_date): Artifact
    {
        $changeset = ChangesetTestBuilder::aChangeset('1')->build();
        $changeset->setFieldValue(
            $this->start_date_field,
            ChangesetValueDateTestBuilder::aValue(1, $changeset, $this->start_date_field)
                ->withTimestamp(strtotime($start_date))
                ->build()
        );
        $changeset->setFieldValue($this->end_date_field, null);

        return ArtifactTestBuilder::anArtifact('4')
            ->withTitle('title')
            ->inTracker(TrackerTestBuilder::aTracker()->build())
            ->withChangesets($changeset)
            ->userCanView(true)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }
}
