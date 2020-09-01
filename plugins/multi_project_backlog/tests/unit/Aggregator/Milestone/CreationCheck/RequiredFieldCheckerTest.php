<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollection;

final class RequiredFieldCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RequiredFieldChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = new RequiredFieldChecker(new NullLogger());
    }

    public function testAllowsCreationWhenOnlySynchronizedFieldsAreRequired(): void
    {
        $required_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $required_field->shouldReceive('isRequired')->andReturn(true);
        $required_field->shouldReceive('getId')->andReturn('789');
        $non_required_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $non_required_field->shouldReceive('isRequired')->andReturn(false);
        $non_required_field->shouldReceive('getId')->andReturn('987');

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getFormElementFields')->andReturn([$required_field, $non_required_field]);
        $tracker->shouldReceive('getGroupId')->andReturn('147');

        $other_tracker_with_no_required_field = \Mockery::mock(\Tracker::class);
        $other_non_required_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $other_non_required_field->shouldReceive('isRequired')->andReturn(false);
        $other_tracker_with_no_required_field->shouldReceive('getFormElementFields')->andReturn([$other_non_required_field]);
        $other_tracker_with_no_required_field->shouldReceive('getGroupId')->andReturn('148');

        $no_other_required_fields = $this->checker->areRequiredFieldsOfContributorTrackersLimitedToTheSynchronizedFields(
            new MilestoneTrackerCollection(\Project::buildForTest(), [$tracker, $other_tracker_with_no_required_field]),
            new SynchronizedFieldCollection([$required_field, $non_required_field])
        );
        $this->assertTrue($no_other_required_fields);
    }

    public function testDisallowsCreationWhenANonSynchronizedFields(): void
    {
        $required_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $required_field->shouldReceive('isRequired')->andReturn(true);
        $required_field->shouldReceive('getId')->andReturn('789');
        $other_required_field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $other_required_field->shouldReceive('isRequired')->andReturn(true);
        $other_required_field->shouldReceive('getId')->andReturn('987');
        $other_required_field->shouldReceive('getLabel')->andReturn('some_labale');

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn('412');
        $tracker->shouldReceive('getFormElementFields')->andReturn([$required_field, $other_required_field]);
        $tracker->shouldReceive('getGroupId')->andReturn('147');

        $no_other_required_fields = $this->checker->areRequiredFieldsOfContributorTrackersLimitedToTheSynchronizedFields(
            new MilestoneTrackerCollection(\Project::buildForTest(), [$tracker]),
            new SynchronizedFieldCollection([$required_field])
        );
        $this->assertFalse($no_other_required_fields);
    }
}
