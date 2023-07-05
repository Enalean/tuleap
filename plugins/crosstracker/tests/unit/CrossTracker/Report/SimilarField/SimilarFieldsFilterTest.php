<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Semantic_Description;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SimilarFieldsFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SimilarFieldsFilter $filter;

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

    public function testFilterCandidatesUsedInSemantics(): void
    {
        $first_tracker  = TrackerTestBuilder::aTracker()->withId(91)->build();
        $second_tracker = TrackerTestBuilder::aTracker()->withId(26)->build();

        $first_field = $this->createMock(\Tracker_FormElement_Field::class);
        $first_field->method('accept')->willReturn(true);

        $second_field = $this->createMock(\Tracker_FormElement_Field::class);
        $second_field->method('accept')->willReturn(false);

        Tracker_Semantic_Title::setInstance($this->createMock(Tracker_Semantic_Title::class), $first_tracker);
        Tracker_Semantic_Title::setInstance($this->createMock(Tracker_Semantic_Title::class), $second_tracker);
        Tracker_Semantic_Description::setInstance($this->createMock(Tracker_Semantic_Description::class), $first_tracker);
        Tracker_Semantic_Description::setInstance($this->createMock(Tracker_Semantic_Description::class), $second_tracker);
        Tracker_Semantic_Status::setInstance($this->createMock(Tracker_Semantic_Status::class), $first_tracker);
        Tracker_Semantic_Status::setInstance($this->createMock(Tracker_Semantic_Status::class), $second_tracker);

        $candidates = $this->buildCandidates([
            ['field' => $first_field, 'tracker' => $first_tracker],
            ['field' => $second_field, 'tracker' => $second_tracker],
        ]);

        $results = $this->filter->filterCandidatesUsedInSemantics(...$candidates);

        self::assertCount(1, $results);
    }

    private function buildCandidates(array $values): array
    {
        $candidates = [];
        foreach ($values as $value) {
            $candidates[] = $this->buildCandidate($value['field'], $value['tracker']);
        }
        return $candidates;
    }

    private function buildCandidate(\Tracker_FormElement_Field $field, \Tracker $tracker): SimilarFieldCandidate&MockObject
    {
        $candidate = $this->createMock(SimilarFieldCandidate::class);
        $candidate->method('getField')->willReturn($field);
        $candidate->method('getTracker')->willReturn($tracker);

        return $candidate;
    }
}
