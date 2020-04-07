<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\SimilarField;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Semantic_Description;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;

class SimilarFieldsFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SimilarFieldsFilter */
    private $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new SimilarFieldsFilter();
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Title::clearInstances();
        Tracker_Semantic_Description::clearInstances();
        Tracker_Semantic_Status::clearInstances();

        parent::tearDown();
    }

    public function testFilterCandidatesUsedInSemantics()
    {
        $first_tracker  = Mockery::mock(\Tracker::class)
            ->shouldReceive('getId')->andReturns(91)
            ->getMock();
        $second_tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive('getId')->andReturns(26)
            ->getMock();

        $first_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $first_field->shouldReceive('accept')->andReturn(true);

        $second_field = Mockery::mock(\Tracker_FormElement_Field::class);
        $second_field->shouldReceive('accept')->andReturn(false);

        Tracker_Semantic_Title::setInstance(Mockery::mock(Tracker_Semantic_Title::class), $first_tracker);
        Tracker_Semantic_Title::setInstance(Mockery::mock(Tracker_Semantic_Title::class), $second_tracker);
        Tracker_Semantic_Description::setInstance(Mockery::mock(Tracker_Semantic_Description::class), $first_tracker);
        Tracker_Semantic_Description::setInstance(Mockery::mock(Tracker_Semantic_Description::class), $second_tracker);
        Tracker_Semantic_Status::setInstance(Mockery::mock(Tracker_Semantic_Status::class), $first_tracker);
        Tracker_Semantic_Status::setInstance(Mockery::mock(Tracker_Semantic_Status::class), $second_tracker);

        $candidates = $this->buildCandidates([
            ['field' => $first_field, 'tracker' => $first_tracker],
            ['field' => $second_field, 'tracker' => $second_tracker]
        ]);

        $results = $this->filter->filterCandidatesUsedInSemantics(...$candidates);

        $this->assertCount(1, $results);
    }

    private function buildCandidates(array $values)
    {
        $candidates = [];
        foreach ($values as $value) {
            $candidates[] = $this->buildCandidate($value['field'], $value['tracker']);
        }
        return $candidates;
    }

    private function buildCandidate($field, $tracker)
    {
        $candidate = Mockery::mock(SimilarFieldCandidate::class);
        $candidate->shouldReceive('getField')->andReturn($field);
        $candidate->shouldReceive('getTracker')->andReturn($tracker);
        return $candidate;
    }
}
