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
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;

final class TimeframeBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /**
     * @var TimeframeBuilder
     */
    private $builder;

    private $formelement_factory;
    private $tracker;
    private $artifact;
    private $user;

    private $semantic_timeframe_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formelement_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);

        $this->builder = new TimeframeBuilder(
            $this->formelement_factory,
            $this->semantic_timeframe_builder
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
        $start_date_field  = null;
        $duration_field    = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoFieldStartDate(): void
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturnNull();
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(0, $time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithDurationAsZeroForArtifactIfNoFieldDuration(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturnNull();

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertSame(0, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturn($start_date_field);

        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(0, $time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithZeroDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturn($duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertSame(0, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForChartWhenStartDateAndDurationAreSet()
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';
        $start_date_field  = null;
        $duration_field    = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );


        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

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
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturnNull();

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenStartDateIsEmptyInChartContext()
    {
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $start_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset->shouldReceive('getTimestamp')->andReturnNull();

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($start_date_changeset);

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturn($start_date_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItBuildsATimePeriodForChartWhenStartDateHasNoLastChangesetValueAndDurationIsSet()
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturn($start_date_field);

        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );

        $this->assertNull($time_period->getStartDate());
    }

    public function testItThrowsAnExceptionWhenNoDurationFieldInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);

        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturnNull();

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
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );


        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

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
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );


        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

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
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

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
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenDurationHasNoLastChangesetValueInChartContext()
    {
        $start_date       = '07/01/2013';
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturn($duration_field);

        $this->expectException(Tracker_FormElement_Chart_Field_Exception::class);

        $this->builder->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->artifact,
            $this->user
        );
    }

    public function testItBuildATimePeriodWithoutWeekObjectForArtifactForREST(): void
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';
        $start_date_field  = null;
        $duration_field    = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithStartDateAsNullForArtifactIfNoFieldStartDate(): void
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturnNull();
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertNull($time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithDurationAsNullForArtifactIfNoFieldDuration(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturnNull();

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertNull($time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectForRESTWithStartDateAsNullForArtifactIfNoLastChangesetValueForStartDate(): void
    {
        $duration         = 10;
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturn($start_date_field);

        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertNull($time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodForRESTWithNullDurationWhenDurationFieldHasNoLastChangeset(): void
    {
        $start_date       = '07/01/2013';
        $start_date_field = null;
        $duration_field   = null;

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $this->mockStartDateFieldWithValue($start_date);
        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturnNull();

        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturn($duration_field);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($start_date), $time_period->getEndDate());
        $this->assertNull($time_period->getDuration());
    }

    public function testItUsesTimeframeSemanticBoundFieldsIfItIsSet() : void
    {
        $duration                     = 10;
        $start_date_field             = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $duration_field               = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $custom_start_date_field_name = 'my_custom_start_date_field';
        $custom_duration_field_name   = 'my_custom_duration_field';

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')->with($this->tracker)->andReturn(
            new SemanticTimeframe($this->tracker, $start_date_field, $duration_field)
        );

        $start_date_field->shouldReceive('getName')->andReturn($custom_start_date_field_name);
        $duration_field->shouldReceive('getName')->andReturn($custom_duration_field_name);

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                $custom_start_date_field_name
            )
            ->andReturnNull();

        $this->mockDurationFieldWithValue($duration, $custom_duration_field_name);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifactForREST($this->artifact, $this->user);

        $this->assertNull($time_period->getStartDate());
        $this->assertSame(1209600, $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    private function mockStartDateFieldWithValue(string $start_date): void
    {
        $start_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset->shouldReceive('getTimestamp')->andReturn(strtotime($start_date));

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($start_date_changeset);

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->tracker,
                $this->user,
                'start_date'
            )
            ->andReturn($start_date_field);
    }

    private function mockDurationFieldWithValue(?int $duration, string $duration_field_name = 'duration'): void
    {
        $duration_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_Integer::class);
        $duration_changeset_value->shouldReceive('getNumeric')->andReturn($duration);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($duration_changeset_value);

        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, $duration_field_name)
            ->andReturn($duration_field);
    }
}
