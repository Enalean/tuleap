<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class SemanticTimeframeDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|SemanticTimeframeDao
     */
    private $dao;
    /**
     * @var SemanticTimeframeDuplicator
     */
    private $duplicator;

    protected function setUp(): void
    {
        $this->dao        = \Mockery::mock(SemanticTimeframeDao::class);
        $this->duplicator = new SemanticTimeframeDuplicator($this->dao);
    }

    public function testItDoesNotDuplicateIfThereIsNoExistingConfig(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(null);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->never();

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicateInSameProject(1, 2, []);
    }

    public function testItDoesNotDuplicateIfThereIsNoStartDateFieldAndNoImpliedFromTracker(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => null, 'duration_field_id' => null, 'end_date_field_id' => null, 'implied_from_tracker_id' => null]);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->never();

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicateInSameProject(1, 2, []);
    }

    public function testItDoesNotDuplicateIfThereIsNoStartDateFieldInMapping(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => null, 'implied_from_tracker_id' => null]);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->never();

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicateInSameProject(1, 2, [['from' => 102, 'to' => 1002]]);
    }

    public function testItDoesNotDuplicateIfThereIsNoDurationFieldAndNoEndDateFieldInMapping(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => 103, 'implied_from_tracker_id' => null]);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->never();

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicateInSameProject(1, 2, [['from' => 101, 'to' => 1001]]);
    }

    public function testItDuplicatesAllTheThingsWithDurationField(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => null, 'implied_from_tracker_id' => null]);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->never();

        $this->dao
            ->shouldReceive('save')
            ->with(2, 1001, 1002, null, null)
            ->once();

        $this->duplicator->duplicateInSameProject(
            1,
            2,
            [
                ['from' => 101, 'to' => 1001],
                ['from' => 102, 'to' => 1002],
            ]
        );
    }

    public function testItDuplicatesAllTheThingsWithEndDateField(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => null, 'end_date_field_id' => 103, 'implied_from_tracker_id' => null]);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->never();

        $this->dao
            ->shouldReceive('save')
            ->with(2, 1001, null, 1003, null)
            ->once();

        $this->duplicator->duplicateInSameProject(
            1,
            2,
            [
                ['from' => 101, 'to' => 1001],
                ['from' => 103, 'to' => 1003],
            ]
        );
    }

    /**
     * @testWith
     *           [null, null, null, 50]
     *           [101, 103, 104, 50]
     *           [101, null, null, 50]
     *           [101, 103, null, 50]
     *           [101, null, 104, 50]
     *           [null, null, 104, 50]
     *           [null, 103, null, 50]
     */
    public function testItDuplicatesAllTheThingsWithImpliedFromTrackerIdFromSameProjectEvenWhenOtherFieldsAreRetrieved(
        ?int $from_start_date_field_id,
        ?int $from_duration_field_id,
        ?int $from_end_date_field_id,
        ?int $from_implied_from_tracker_id,
    ): void {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn([
                'start_date_field_id' => $from_start_date_field_id,
                'duration_field_id' => $from_duration_field_id,
                'end_date_field_id' => $from_end_date_field_id,
                'implied_from_tracker_id' => $from_implied_from_tracker_id,
            ]);

        $this->dao
            ->shouldReceive('retrieveImpliedFromTrackerId')
            ->with($from_implied_from_tracker_id, 201)
            ->andReturn(500);

        $this->dao
            ->shouldReceive('save')
            ->with(2, null, null, null, 50)
            ->once();

        $this->duplicator->duplicateInSameProject(
            1,
            2,
            [
                ['from' => $from_start_date_field_id, 'to' => null],
                ['from' => $from_duration_field_id, 'to' => null],
                ['from' => $from_end_date_field_id, 'to' => null],
            ]
        );
    }

    /**
     * @testWith
     *           [null, null, null, 50]
     *           [101, 103, 104, 50]
     *           [101, null, null, 50]
     *           [101, 103, null, 50]
     *           [101, null, 104, 50]
     *           [null, null, 104, 50]
     *           [null, 103, null, 50]
     */
    public function testItDuplicatesAllTheThingsWithImpliedFromTrackerIdWhenDuplicatingWholeProjectEvenWhenOtherFieldsAreRetrieved(
        ?int $from_start_date_field_id,
        ?int $from_duration_field_id,
        ?int $from_end_date_field_id,
        ?int $from_implied_from_tracker_id,
    ): void {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(50);

        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(60)
            ->once()
            ->andReturn([
                'start_date_field_id' => $from_start_date_field_id,
                'duration_field_id' => $from_duration_field_id,
                'end_date_field_id' => $from_end_date_field_id,
                'implied_from_tracker_id' => $from_implied_from_tracker_id,
            ]);

        $this->dao
            ->shouldReceive('save')
            ->with(600, null, null, null, 500)
            ->once();

        $this->duplicator->duplicateSemanticTimeframeForAllTrackers(
            [
                ['from' => $from_start_date_field_id, 'to' => null],
                ['from' => $from_duration_field_id, 'to' => null],
                ['from' => $from_end_date_field_id, 'to' => null],
            ],
            [
                50 => 500,
                60 => 600,
            ]
        );
    }

    public function testItDuplicatesEmptySemanticWhenDuplicatingWholeProjectWithImpliedFromTrackerIdNotFoundInTrackerMapping(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(50);

        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(60)
            ->once()
            ->andReturn([
                'start_date_field_id' => 1,
                'duration_field_id' => 2,
                'end_date_field_id' => 3,
                'implied_from_tracker_id' => 50,
            ]);

        $this->dao
            ->shouldReceive('save')
            ->with(600, null, null, null, null)
            ->once();

        $this->duplicator->duplicateSemanticTimeframeForAllTrackers(
            [
                ['from' => 1, 'to' => null],
                ['from' => 2, 'to' => null],
                ['from' => 3, 'to' => null],
            ],
            [
                60 => 600,
            ]
        );
    }

    public function testItDoesNothingWhenAskingToDuplicateFromFieldsWhenThisIsNotConfigured(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(101)
            ->once()
            ->andReturn([
                'start_date_field_id' => 1,
                'duration_field_id' => 2,
                'end_date_field_id' => 3,
                'implied_from_tracker_id' => 50,
            ]);

        $this->dao->shouldNotReceive('save');

        $this->duplicator->duplicateBasedOnFieldConfiguration(
            101,
            201,
            []
        );
    }

    public function testItDuplicatesWhenAskingToDuplicateFromFieldsWhenThisIsConfigured(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(101)
            ->once()
            ->andReturn([
                'start_date_field_id' => 1,
                'duration_field_id' => 2,
                'end_date_field_id' => 3,
                'implied_from_tracker_id' => null,
            ]);

        $this->dao
            ->shouldReceive('save')
            ->with(201, 1001, null, 3001, null)
            ->once();

        $this->duplicator->duplicateBasedOnFieldConfiguration(
            101,
            201,
            [
                ['from' => 1, 'to' => 1001],
                ['from' => 3, 'to' => 3001],
            ]
        );
    }
}
