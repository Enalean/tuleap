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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SimilarFieldCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItKeepsFieldsWithTheSameNameAndTypeInAtLeastTwoTrackers()
    {
        $candidates = $this->buildCandidates([
            ['name' => 'alternator', 'tracker_id' => 91, 'type' => 'string'],
            ['name' => 'alternator', 'tracker_id' => 36, 'type' => 'string'],
            ['name' => 'alternator', 'tracker_id' => 32, 'type' => 'string'],
            ['name' => 'acroscleriasis', 'tracker_id' => 91, 'type' => 'sb'],
            ['name' => 'acroscleriasis', 'tracker_id' => 32, 'type' => 'sb'],
        ]);
        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertSame($candidates, iterator_to_array($collection));
        $this->assertSame(['alternator', 'acroscleriasis'], $collection->getFieldNames());

        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getTrackerId')->andReturns(91);
        $this->assertSame($candidates[0]->getField(), $collection->getField($artifact, 'alternator'));
        $this->assertNull($collection->getField($artifact, 'not_candidate'));
    }

    public function testItFiltersOutFieldsInOnlyOneTracker()
    {
        $candidates = $this->buildCandidates([
            ['name' => 'archegonium', 'tracker_id' => 87, 'type' => 'int'],
            ['name' => 'Cassiepeia', 'tracker_id' => 85, 'type' => 'sb'],
        ]);


        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertCount(0, $collection);
    }

    public function testItFiltersOutFieldsWithTheSameNameButNotTheSameType()
    {
        $candidates = $this->buildCandidates([
            ['name' => 'floriculturally', 'tracker_id' => 89, 'type' => 'sb'],
            ['name' => 'floriculturally', 'tracker_id' => 98, 'type' => 'rb'],
        ]);


        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertCount(0, $collection);
    }

    public function testItWorksWithoutCandidates()
    {
        $collection = new SimilarFieldCollection();
        $this->assertCount(0, $collection);
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getTrackerId')->andReturns(101);
        $this->assertNull($collection->getField($artifact, 'name'));
        $this->assertEmpty($collection->getFieldNames());
    }

    private function buildCandidates(array $values)
    {
        $candidates = [];
        foreach ($values as $value) {
            $candidates[] = $this->buildCandidate($value['name'], $value['tracker_id'], $value['type']);
        }
        return $candidates;
    }

    private function buildCandidate($name, $tracker_id, $type)
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getName')->andReturns($name);
        $field->shouldReceive('getTrackerId')->andReturns($tracker_id);
        $candidate = \Mockery::mock(SimilarFieldCandidate::class);
        $candidate->shouldReceive('getIdentifier')->andReturns($type . '/' . $name);
        $candidate->shouldReceive('getField')->andReturns($field);
        return $candidate;
    }
}
