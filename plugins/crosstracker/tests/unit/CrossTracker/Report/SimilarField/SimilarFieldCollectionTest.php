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
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SimilarFieldCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItKeepsFieldsWithTheSameNameAndTypeInAtLeastTwoTrackers(): void
    {
        $candidates = $this->buildCandidates([
            ['name' => 'alternator', 'tracker_id' => 91, 'type' => 'string', 'bind_name' => null],
            ['name' => 'alternator', 'tracker_id' => 36, 'type' => 'string', 'bind_name' => null],
            ['name' => 'alternator', 'tracker_id' => 32, 'type' => 'string', 'bind_name' => null],
            ['name' => 'acroscleriasis', 'tracker_id' => 91, 'type' => 'sb', 'bind_name' => 'static'],
            ['name' => 'acroscleriasis', 'tracker_id' => 32, 'type' => 'sb', 'bind_name' => 'static'],
        ]);
        $collection = new SimilarFieldCollection(...$candidates);
        self::assertSame($candidates, iterator_to_array($collection));
        self::assertSame(['alternator', 'acroscleriasis'], $collection->getFieldNames());
        self::assertCount(2, $collection->getFieldIdentifiers());

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker(TrackerTestBuilder::aTracker()->withId(91)->build())->build();

        $identifier = new SimilarFieldIdentifier('alternator', null);
        self::assertSame($candidates[0]->getField(), $collection->getField($artifact, $identifier));
        $not_a_candidate = new SimilarFieldIdentifier('not_candidate', null);
        self::assertNull($collection->getField($artifact, $not_a_candidate));
    }

    public function testItFiltersOutFieldsInOnlyOneTracker(): void
    {
        $candidates = $this->buildCandidates([
            ['name' => 'archegonium', 'tracker_id' => 87, 'type' => 'int', 'bind_name' => null],
            ['name' => 'Cassiepeia', 'tracker_id' => 85, 'type' => 'sb', 'bind_name' => 'static'],
        ]);

        $collection = new SimilarFieldCollection(...$candidates);
        self::assertCount(0, $collection);
    }

    public function testItFiltersOutFieldsWithTheSameNameButNotTheSameType(): void
    {
        $candidates = $this->buildCandidates([
            ['name' => 'floriculturally', 'tracker_id' => 89, 'type' => 'sb', 'bind_name' => 'static'],
            ['name' => 'floriculturally', 'tracker_id' => 98, 'type' => 'rb', 'bind_name' => 'static'],
        ]);

        $collection = new SimilarFieldCollection(...$candidates);
        self::assertCount(0, $collection);
    }

    public function testItFiltersOutFieldsWithTheSameNameAndTypeButNotTheSameBind(): void
    {
        $candidates = $this->buildCandidates(
            [
                ['name' => 'floriculturally', 'tracker_id' => 89, 'type' => 'sb', 'bind_name' => 'static'],
                ['name' => 'floriculturally', 'tracker_id' => 98, 'type' => 'sb', 'bind_name' => 'users'],
            ]
        );

        $collection = new SimilarFieldCollection(...$candidates);
        self::assertCount(0, $collection);
    }

    public function testItWorksWithoutCandidates(): void
    {
        $collection = new SimilarFieldCollection();
        self::assertCount(0, $collection);
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('getTrackerId')->willReturn(101);
        $not_a_candidate = new SimilarFieldIdentifier('name', null);
        self::assertNull($collection->getField($artifact, $not_a_candidate));
        self::assertEmpty($collection->getFieldNames());
        self::assertEmpty($collection->getFieldIdentifiers());
    }

    private function buildCandidates(array $values): array
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

    private function buildCandidate(string $name, int $tracker_id, string $type, ?string $bind_name): SimilarFieldCandidate&MockObject
    {
        $field = $this->createMock(\Tracker_FormElement_Field::class);
        $field->method('getTrackerId')->willReturn($tracker_id);
        $candidate = $this->createMock(SimilarFieldCandidate::class);
        $candidate->method('getTypeWithBind')->willReturn($type . '/' . $bind_name . '/' . $name);
        $candidate->method('getIdentifierWithBindType')->willReturn($name . '/' . $bind_name);
        $candidate->method('getField')->willReturn($field);

        return $candidate;
    }
}
