<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;
use Tuleap\GlobalLanguageMock;

final class TimeframeBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var TimeframeBuilder
     */
    private $builder;

    /**
     * @var Mockery\MockInterface|Tracker
     */
    private $tracker;
    private $artifact;
    private $user;

    private $semantic_timeframe_builder;
    /**
     * @var LoggerInterface|Mockery\MockInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);

        $this->logger  = Mockery::mock(LoggerInterface::class);
        $this->builder = new TimeframeBuilder(
            $this->semantic_timeframe_builder,
            $this->logger
        );

        $this->tracker  = Mockery::mock(Tracker::class);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->user     = Mockery::mock(PFUser::class);

        $this->tracker->shouldReceive('getId')->andReturn(1);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
    }

    public function testItBuildATimePeriodWithoutWeekObjectForArtifact(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';
        $start_date_field  = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoFieldStartDate(): void
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field   = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(0, $time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithDurationAsZeroForArtifactIfNoFieldDuration(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertSame(0, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration = 10;
        $start_date_field  = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $start_date_field->shouldReceive('getLastChangesetValue')
                         ->with($this->artifact)
                         ->andReturnNull();

        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(0, $time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithZeroDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertSame(0, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithEndDateForArtifact(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $end_date       = '07/03/2013';
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($this->tracker)
            ->andReturn(
                new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
            );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockEndDateFieldWithValue($end_date, $end_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($end_date), $time_period->getEndDate());
        $this->assertSame(2, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithEndDateForArtifactWithZeroForEndDateIfUserCannotRead(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $end_date       = '07/03/2013';
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(false);

        $this->logger->shouldReceive('warning')->once();

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($this->tracker)
            ->andReturn(
                new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
            );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockEndDateFieldWithValue($end_date, $end_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(0, $time_period->getEndDate());
        // duration between start date (07/01/2013) and 01/01/1970 since user cannot read the field.
        // Weird but consistent with date field/duration behavior.
        $this->assertSame(-11347, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithEndDateForArtifactWithZeroForEndDateIfNoLastChangesetValue(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);

        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->logger->shouldReceive('warning')->once();

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($this->tracker)
            ->andReturn(
                new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
            );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $end_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(0, $time_period->getEndDate());
        // duration between start date (07/01/2013) and 01/01/1970 since user cannot read the field.
        // Weird but consistent with date field/duration behavior.
        $this->assertSame(-11347, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForChartWhenStartDateAndDurationAreSet()
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItThrowsAnExceptionWhenNoStartDateFieldInChartContext()
    {
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenStartDateIsEmptyInChartContext()
    {
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $start_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset->shouldReceive('getTimestamp')->andReturnNull();

        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($start_date_changeset);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItBuildsATimePeriodForChartWhenStartDateHasNoLastChangesetValueAndDurationIsSet()
    {
        $duration         = 10;
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );

        $this->assertNull($time_period->getStartDate());
    }

    public function testItThrowsAnExceptionWhenNoDurationFieldInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenDurationIsMinorThanZeroInChartContext()
    {
        $duration         = -1;
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenDurationIsEqualToZeroInChartContext()
    {
        $duration         = 0;
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenDurationIsEqualToOneInChartContext()
    {
        $duration         = 1;
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenDurationIsNullInChartContext()
    {
        $duration         = null;
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenDurationHasNoLastChangesetValueInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenEndDateHasNoLastChangesetValueInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $end_date_field->shouldReceive('getLastChangesetValue')
                       ->with($this->artifact)
                       ->andReturnNull();

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenEndDateIsNotReadableInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(false);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenEndDateIsEmpty()
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);

        $end_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $end_date_changeset->shouldReceive('getTimestamp')->andReturn(0);
        $end_date_field->shouldReceive('getLastChangesetValue')
                       ->with($this->artifact)
                       ->andReturns($end_date_changeset);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItReturnsTimeframeFromEndDateInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);

        $end_date       = '07/15/2013';
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockEndDateFieldWithValue($end_date, $end_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildATimePeriodWithoutWeekObjectForArtifactForREST(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithStartDateAsNullForArtifactIfNoFieldStartDate(): void
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertNull($time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithDurationAsNullForArtifactIfNoFieldDuration(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertNull($time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithStartDateAsNullForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration         = 10;
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->mockDurationFieldWithValue($duration, $duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertNull($time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForRESTWithNullDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field, null)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertNull($time_period->getDuration());
    }

    public function testItBuildsATimePeriodForRESTFromEndDate(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);

        $end_date       = '07/03/2013';
        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $this->mockEndDateFieldWithValue($end_date, $end_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($end_date), $time_period->getEndDate());
        $this->assertEquals(2, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForRESTFromEndDateWithNullIfEndDateIsNotReadable(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);

        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(false);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertNull($time_period->getEndDate());
        $this->assertNull($time_period->getDuration());
    }

    public function testItBuildsATimePeriodForRESTFromEndDateWithNullIfEndDateHasNoValue(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('userCanRead')->andReturn(true);

        $end_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('userCanRead')->andReturn(true);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, null, $end_date_field)
        );

        $this->mockStartDateFieldWithValue($start_date, $start_date_field);
        $end_date_field->shouldReceive('getLastChangesetValue')
                       ->with($this->artifact)
                       ->andReturnNull();

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertNull($time_period->getEndDate());
        $this->assertNull($time_period->getDuration());
    }

    private function mockStartDateFieldWithValue(string $start_date, Mockery\MockInterface $start_date_field): void
    {
        $start_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset->shouldReceive('getTimestamp')->andReturn(strtotime($start_date));

        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($start_date_changeset);
    }

    private function mockEndDateFieldWithValue(string $end_date, Mockery\MockInterface $end_date_field): void
    {
        $end_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $end_date_changeset->shouldReceive('getTimestamp')->andReturn(strtotime($end_date));

        $end_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($end_date_changeset);
    }

    private function mockDurationFieldWithValue(?int $duration, Mockery\MockInterface $duration_field): void
    {
        $duration_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_Integer::class);
        $duration_changeset_value->shouldReceive('getNumeric')->andReturn($duration);

        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($duration_changeset_value);
    }
}
