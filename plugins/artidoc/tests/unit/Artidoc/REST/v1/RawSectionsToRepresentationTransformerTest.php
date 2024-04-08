<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use PFUser;
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class RawSectionsToRepresentationTransformerTest extends TestCase
{
    public function testHappyPath(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dao = $this->createMock(\Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = ArtifactTestBuilder::anArtifact(1)->userCanView($user)->build();
        $art2 = ArtifactTestBuilder::anArtifact(2)->userCanView($user)->build();
        $art3 = ArtifactTestBuilder::anArtifact(3)->userCanView($user)->build();
        $art4 = ArtifactTestBuilder::anArtifact(4)->userCanView($user)->build();

        $factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $art1_representation = $this->createMock(ArtifactRepresentation::class);
        $art2_representation = $this->createMock(ArtifactRepresentation::class);
        $art3_representation = $this->createMock(ArtifactRepresentation::class);
        $art4_representation = $this->createMock(ArtifactRepresentation::class);

        $builder = $this->createMock(ArtifactRepresentationBuilder::class);
        $builder->method('getArtifactRepresentationWithFieldValues')
            ->willReturnCallback(
                fn (PFUser $user, Artifact $artifact) => match ($artifact->getId()) {
                    1 => $art1_representation,
                    2 => $art2_representation,
                    3 => $art3_representation,
                    4 => $art4_representation,
                }
            );

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
            $builder,
            fn () => $this->createMock(TrackerRepresentation::class),
            fn () => StatusValueRepresentation::buildFromValues('done', null),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRawSections(
                101,
                [
                    ['artifact_id' => 1],
                    ['artifact_id' => 2],
                    ['artifact_id' => 3],
                    ['artifact_id' => 4],
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(4, $result->value->sections);
        self::assertSame($art1_representation, $result->value->sections[0]->artifact);
        self::assertSame($art2_representation, $result->value->sections[1]->artifact);
        self::assertSame($art3_representation, $result->value->sections[2]->artifact);
        self::assertSame($art4_representation, $result->value->sections[3]->artifact);
    }

    public function testFaultWhenThereIsAtLeastOneArtifactThatCurrentUserCannotRead(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dao = $this->createMock(\Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = ArtifactTestBuilder::anArtifact(1)->userCanView($user)->build();
        $art2 = ArtifactTestBuilder::anArtifact(2)->userCanView($user)->build();
        $art3 = ArtifactTestBuilder::anArtifact(3)->userCannotView($user)->build();
        $art4 = ArtifactTestBuilder::anArtifact(4)->userCanView($user)->build();

        $factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $builder = $this->createMock(ArtifactRepresentationBuilder::class);

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
            $builder,
            fn () => $this->createMock(TrackerRepresentation::class),
            fn () => StatusValueRepresentation::buildFromValues('done', null),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRawSections(
                101,
                [
                    ['artifact_id' => 1],
                    ['artifact_id' => 2],
                    ['artifact_id' => 3],
                    ['artifact_id' => 4],
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenThereIsNotAnyArtifacts(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dao = $this->createMock(\Tracker_ArtifactDao::class);

        $factory = $this->createMock(\Tracker_ArtifactFactory::class);

        $builder = $this->createMock(ArtifactRepresentationBuilder::class);

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
            $builder,
            fn () => $this->createMock(TrackerRepresentation::class),
            fn () => StatusValueRepresentation::buildFromValues('done', null),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRawSections(
                101,
                [],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(0, $result->value->sections);
    }
}
