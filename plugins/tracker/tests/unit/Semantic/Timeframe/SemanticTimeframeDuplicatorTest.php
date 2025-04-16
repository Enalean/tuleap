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

use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticTimeframeDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&SemanticTimeframeDao $dao;
    private SemanticTimeframeDuplicator $duplicator;

    protected function setUp(): void
    {
        $this->dao        = $this->createMock(SemanticTimeframeDao::class);
        $this->duplicator = new SemanticTimeframeDuplicator($this->dao);
    }

    public function testItDoesNotDuplicateIfThereIsNoExistingConfig(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(null);

        $this->dao
            ->expects($this->never())
            ->method('save');

        $this->duplicator->duplicateInSameProject(1, 2, []);
    }

    public function testItDoesNotDuplicateIfThereIsNoStartDateFieldAndNoImpliedFromTracker(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(['start_date_field_id' => null, 'duration_field_id' => null, 'end_date_field_id' => null, 'implied_from_tracker_id' => null]);

        $this->dao
            ->expects($this->never())
            ->method('save');

        $this->duplicator->duplicateInSameProject(1, 2, []);
    }

    public function testItDoesNotDuplicateIfThereIsNoStartDateFieldInMapping(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => null, 'implied_from_tracker_id' => null]);

        $this->dao
            ->expects($this->never())
            ->method('save');

        $this->duplicator->duplicateInSameProject(1, 2, [['from' => 102, 'to' => 1002]]);
    }

    public function testItDoesNotDuplicateIfThereIsNoDurationFieldAndNoEndDateFieldInMapping(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => 103, 'implied_from_tracker_id' => null]);

        $this->dao
            ->expects($this->never())
            ->method('save');

        $this->duplicator->duplicateInSameProject(1, 2, [['from' => 101, 'to' => 1001]]);
    }

    public function testItDuplicatesAllTheThingsWithDurationField(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => null, 'implied_from_tracker_id' => null]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(2, 1001, 1002, null, null);

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
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(['start_date_field_id' => 101, 'duration_field_id' => null, 'end_date_field_id' => 103, 'implied_from_tracker_id' => null]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(2, 1001, null, 1003, null);

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
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn([
                'start_date_field_id' => $from_start_date_field_id,
                'duration_field_id' => $from_duration_field_id,
                'end_date_field_id' => $from_end_date_field_id,
                'implied_from_tracker_id' => $from_implied_from_tracker_id,
            ]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(2, null, null, null, 50);

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
        $this->dao
            ->expects($this->exactly(2))
            ->method('searchByTrackerId')
            ->willReturnCallback(static fn (int $tracker_id) => match ($tracker_id) {
                50 => null,
                60 => [
                    'start_date_field_id' => $from_start_date_field_id,
                    'duration_field_id' => $from_duration_field_id,
                    'end_date_field_id' => $from_end_date_field_id,
                    'implied_from_tracker_id' => $from_implied_from_tracker_id,
                ],
            });

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(600, null, null, null, 500);

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
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(60)
            ->willReturn([
                'start_date_field_id' => 1,
                'duration_field_id' => 2,
                'end_date_field_id' => 3,
                'implied_from_tracker_id' => 50,
            ]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(600, null, null, null, null);

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
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(101)
            ->willReturn([
                'start_date_field_id' => 1,
                'duration_field_id' => 2,
                'end_date_field_id' => 3,
                'implied_from_tracker_id' => 50,
            ]);

        $this->dao->expects($this->never())->method('save');

        $this->duplicator->duplicateBasedOnFieldConfiguration(
            101,
            201,
            []
        );
    }

    public function testItDuplicatesWhenAskingToDuplicateFromFieldsWhenThisIsConfigured(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(101)
            ->willReturn([
                'start_date_field_id' => 1,
                'duration_field_id' => 2,
                'end_date_field_id' => 3,
                'implied_from_tracker_id' => null,
            ]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(201, 1001, null, 3001, null);

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
