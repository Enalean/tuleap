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
use PHPUnit\Framework\TestCase;

class SemanticTimeframeDuplicatorTest extends TestCase
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
        $this->dao = \Mockery::mock(SemanticTimeframeDao::class);
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
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, []);
    }



    public function testItDoesNotDuplicateIfThereIsNoDurationFieldAndNoEndDateFieldInConfig(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => null, 'end_date_field_id' => null]);

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, []);
    }

    public function testItDoesNotDuplicateIfThereIsNoStartDateFieldInMapping(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => null]);

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, [['from' => 102, 'to' => 1002]]);
    }

    public function testItDoesNotDuplicateIfThereIsNoDurationFieldAndNoEndDateFieldInMapping(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => 103]);

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, [['from' => 101, 'to' => 1001]]);
    }

    public function testItDuplicatesAllTheThingsWithDurationField(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => 102, 'end_date_field_id' => null]);

        $this->dao
            ->shouldReceive('save')
            ->with(2, 1001, 1002, null)
            ->once();

        $this->duplicator->duplicate(
            1,
            2,
            [
                ['from' => 101, 'to' => 1001],
                ['from' => 102, 'to' => 1002]
            ]
        );
    }

    public function testItDuplicatesAllTheThingsWithEndDateField(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['start_date_field_id' => 101, 'duration_field_id' => null, 'end_date_field_id' => 103]);

        $this->dao
            ->shouldReceive('save')
            ->with(2, 1001, null, 1003)
            ->once();

        $this->duplicator->duplicate(
            1,
            2,
            [
                ['from' => 101, 'to' => 1001],
                ['from' => 103, 'to' => 1003]
            ]
        );
    }
}
