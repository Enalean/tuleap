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
            ['name' => 'alternator', 'tracker_id' => 91, 'type' => 'string', 'bind_name' => null],
            ['name' => 'alternator', 'tracker_id' => 36, 'type' => 'string', 'bind_name' => null],
            ['name' => 'alternator', 'tracker_id' => 32, 'type' => 'string', 'bind_name' => null],
            ['name' => 'acroscleriasis', 'tracker_id' => 91, 'type' => 'sb', 'bind_name' => 'static'],
            ['name' => 'acroscleriasis', 'tracker_id' => 32, 'type' => 'sb', 'bind_name' => 'static'],
        ]);
        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertSame($candidates, iterator_to_array($collection));
        $this->assertSame(['alternator', 'acroscleriasis'], $collection->getFieldNames());
        $this->assertCount(2, $collection->getFieldIdentifiers());

        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getTrackerId')->andReturns(91);
        $identifier = new SimilarFieldIdentifier('alternator', null);
        $this->assertSame($candidates[0]->getField(), $collection->getField($artifact, $identifier));
        $not_a_candidate = new SimilarFieldIdentifier('not_candidate', null);
        $this->assertNull($collection->getField($artifact, $not_a_candidate));
    }

    public function testItFiltersOutFieldsInOnlyOneTracker()
    {
        $candidates = $this->buildCandidates([
            ['name' => 'archegonium', 'tracker_id' => 87, 'type' => 'int', 'bind_name' => null],
            ['name' => 'Cassiepeia', 'tracker_id' => 85, 'type' => 'sb', 'bind_name' => 'static'],
        ]);

        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertCount(0, $collection);
    }

    public function testItFiltersOutFieldsWithTheSameNameButNotTheSameType()
    {
        $candidates = $this->buildCandidates([
            ['name' => 'floriculturally', 'tracker_id' => 89, 'type' => 'sb', 'bind_name' => 'static'],
            ['name' => 'floriculturally', 'tracker_id' => 98, 'type' => 'rb', 'bind_name' => 'static'],
        ]);

        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertCount(0, $collection);
    }

    public function testItFiltersOutFieldsWithTheSameNameAndTypeButNotTheSameBind()
    {
        $candidates = $this->buildCandidates(
            [
                ['name' => 'floriculturally', 'tracker_id' => 89, 'type' => 'sb', 'bind_name' => 'static'],
                ['name' => 'floriculturally', 'tracker_id' => 98, 'type' => 'sb', 'bind_name' => 'users'],
            ]
        );

        $collection = new SimilarFieldCollection(...$candidates);
        $this->assertCount(0, $collection);
    }

    public function testItWorksWithoutCandidates()
    {
        $collection = new SimilarFieldCollection();
        $this->assertCount(0, $collection);
        $artifact = \Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getTrackerId')->andReturns(101);
        $not_a_candidate = new SimilarFieldIdentifier('name', null);
        $this->assertNull($collection->getField($artifact, $not_a_candidate));
        $this->assertEmpty($collection->getFieldNames());
        $this->assertEmpty($collection->getFieldIdentifiers());
    }

    private function buildCandidates(array $values)
    {
        $candidates = [];
        foreach ($values as $value) {
            $candidates[] = $this->buildCandidate(
                $value['name'],
                $value['tracker_id'],
                $value['type'],
                $value['bind_name']
            );
        }
        return $candidates;
    }

    private function buildCandidate($name, $tracker_id, $type, $bind_name)
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getTrackerId')->andReturns($tracker_id);
        $candidate = \Mockery::mock(SimilarFieldCandidate::class);
        $candidate->shouldReceive('getTypeWithBind')->andReturns($type . '/' . $bind_name . '/' . $name);
        $candidate->shouldReceive('getIdentifierWithBindType')->andReturns($name . '/' . $bind_name);
        $candidate->shouldReceive('getField')->andReturns($field);
        return $candidate;
    }
}
