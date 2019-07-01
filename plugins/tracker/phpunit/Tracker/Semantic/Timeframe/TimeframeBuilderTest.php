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
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElementFactory;

final class TimeframeBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TimeframeBuilder
     */
    private $builder;

    private $formelement_factory;
    private $tracker;
    private $artifact;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formelement_factory = Mockery::mock(Tracker_FormElementFactory::class);

        $this->builder = new TimeframeBuilder($this->formelement_factory);

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

        $this->mockStartDateFieldWithValue($start_date);
        $this->mockDurationFieldWithValue($duration);

        $time_period = $this->builder->buildTimePeriodWithoutWeekendForArtifact($this->artifact, $this->user);

        $this->assertSame(strtotime($start_date), $time_period->getStartDate());
        $this->assertSame(strtotime($expected_end_date), $time_period->getEndDate());
        $this->assertSame(10, $time_period->getDuration());
    }

    public function testItBuildsATimePeriodWithoutWeekObjectWithStartDateAsZeroForArtifactIfNoFieldStartDate(): void
    {
        $duration = 10;

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
        $start_date = '07/01/2013';

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
        $duration = 10;

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
        $start_date = '07/01/2013';

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

    private function mockDurationFieldWithValue(int $duration): void
    {
        $duration_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_Integer::class);
        $duration_changeset_value->shouldReceive('getNumeric')->andReturn($duration);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($duration_changeset_value);

        $this->formelement_factory->shouldReceive('getNumericFieldByNameForUser')
            ->with($this->tracker, $this->user, 'duration')
            ->andReturn($duration_field);
    }
}
