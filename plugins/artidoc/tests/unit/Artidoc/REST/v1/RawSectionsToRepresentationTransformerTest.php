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
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Description;
use Tracker_Semantic_Title;
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueCommonmarkRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class RawSectionsToRepresentationTransformerTest extends TestCase
{
    private Tracker $tracker;
    private Tracker_Semantic_Title&MockObject $semantic_title;
    private Tracker_Semantic_Description&MockObject $semantic_description;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->semantic_title       = $this->createMock(Tracker_Semantic_Title::class);
        $this->semantic_description = $this->createMock(Tracker_Semantic_Description::class);

        Tracker_Semantic_Title::setInstance($this->semantic_title, $this->tracker);
        Tracker_Semantic_Description::setInstance($this->semantic_description, $this->tracker);
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Title::clearInstances();
    }

    private function getArtifact(int $id, Tracker_FormElement_Field_String $title, PFUser $user): Artifact
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);

        $this->setTitleValue($title, $changeset, $id);

        return ArtifactTestBuilder::anArtifact($id)
            ->inTracker($this->tracker)
            ->withChangesets($changeset)
            ->userCanView($user)
            ->build();
    }

    private function getArtifactUserCannotView(int $id, PFUser $user): Artifact
    {
        return ArtifactTestBuilder::anArtifact($id)->userCannotView($user)->build();
    }

    private function setTitleValue(Tracker_FormElement_Field_String $title, Tracker_Artifact_Changeset & MockObject $changeset, int $id): void
    {
        $changeset->method('getValue')
            ->with($title)
            ->willReturn(ChangesetValueTextTestBuilder::aValue(1, $changeset, $title)->withValue("Title for #{$id}")->build());
    }

    private function getDescriptionValue(Artifact $artifact): ArtifactFieldValueCommonmarkRepresentation
    {
        return new ArtifactFieldValueCommonmarkRepresentation(
            100 * $artifact->getId(),
            'text',
            'Description',
            "Desc <b>for</b> #{$artifact->getId()}",
            "Desc **for** #{$artifact->getId()}",
            "Desc <b>for</b> #{$artifact->getId()}",
        );
    }

    public function testHappyPath(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = $this->createMock(Tracker_FormElement_Field_String::class);
        $title->method('userCanRead')->willReturn(true);
        $this->semantic_title->method('getField')->willReturn($title);

        $description = $this->createMock(Tracker_FormElement_Field_Text::class);
        $description->method('userCanRead')->willReturn(true);
        $this->semantic_description->method('getField')->willReturn($description);

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = $this->getArtifact(1, $title, $user);
        $art2 = $this->getArtifact(2, $title, $user);
        $art3 = $this->getArtifact(3, $title, $user);
        $art4 = $this->getArtifact(4, $title, $user);

        $description->method('getFullRESTValue')
            ->willReturnCallback(fn (PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getDescriptionValue($art1),
                $art2->getLastChangeset() => $this->getDescriptionValue($art2),
                $art3->getLastChangeset() => $this->getDescriptionValue($art3),
                $art4->getLastChangeset() => $this->getDescriptionValue($art4),
            });

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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

        $expected = [
            ['id' => 1, 'title' => 'Title for #1', 'description' => 'Desc <b>for</b> #1'],
            ['id' => 2, 'title' => 'Title for #2', 'description' => 'Desc <b>for</b> #2'],
            ['id' => 3, 'title' => 'Title for #3', 'description' => 'Desc <b>for</b> #3'],
            ['id' => 4, 'title' => 'Title for #4', 'description' => 'Desc <b>for</b> #4'],
        ];
        array_walk(
            $expected,
            static function (array $expected, int $index) use ($result) {
                self::assertSame($expected['id'], $result->value->sections[$index]->artifact->id);
                self::assertSame($expected['title'], $result->value->sections[$index]->title);
                self::assertInstanceOf(ArtifactFieldValueCommonmarkRepresentation::class, $result->value->sections[$index]->description);
                self::assertSame($expected['description'], $result->value->sections[$index]->description->value);
            }
        );
    }

    public function testWhenTitleSemanticIsNotSet(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = $this->createMock(Tracker_FormElement_Field_String::class);
        $title->method('userCanRead')->willReturn(true);
        $this->semantic_title->method('getField')->willReturn(null);

        $description = $this->createMock(Tracker_FormElement_Field_Text::class);
        $description->method('userCanRead')->willReturn(true);
        $this->semantic_description->method('getField')->willReturn($description);

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = $this->getArtifact(1, $title, $user);
        $art2 = $this->getArtifact(2, $title, $user);
        $art3 = $this->getArtifact(3, $title, $user);
        $art4 = $this->getArtifact(4, $title, $user);

        $description->method('getFullRESTValue')
            ->willReturnCallback(fn (PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getDescriptionValue($art1),
                $art2->getLastChangeset() => $this->getDescriptionValue($art2),
                $art3->getLastChangeset() => $this->getDescriptionValue($art3),
                $art4->getLastChangeset() => $this->getDescriptionValue($art4),
            });

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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

    public function testWhenTitleSemanticIsNotReadableByCurrentUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = $this->createMock(Tracker_FormElement_Field_String::class);
        $title->method('userCanRead')->willReturn(false);
        $this->semantic_title->method('getField')->willReturn($title);

        $description = $this->createMock(Tracker_FormElement_Field_Text::class);
        $description->method('userCanRead')->willReturn(true);
        $this->semantic_description->method('getField')->willReturn($description);

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = $this->getArtifact(1, $title, $user);
        $art2 = $this->getArtifact(2, $title, $user);
        $art3 = $this->getArtifact(3, $title, $user);
        $art4 = $this->getArtifact(4, $title, $user);

        $description->method('getFullRESTValue')
            ->willReturnCallback(fn (PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getDescriptionValue($art1),
                $art2->getLastChangeset() => $this->getDescriptionValue($art2),
                $art3->getLastChangeset() => $this->getDescriptionValue($art3),
                $art4->getLastChangeset() => $this->getDescriptionValue($art4),
            });

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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

    public function testWhenDescriptionSemanticIsNotReadableByCurrentUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = $this->createMock(Tracker_FormElement_Field_String::class);
        $title->method('userCanRead')->willReturn(true);
        $this->semantic_title->method('getField')->willReturn($title);

        $description = $this->createMock(Tracker_FormElement_Field_Text::class);
        $description->method('userCanRead')->willReturn(false);
        $this->semantic_description->method('getField')->willReturn($description);

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = $this->getArtifact(1, $title, $user);
        $art2 = $this->getArtifact(2, $title, $user);
        $art3 = $this->getArtifact(3, $title, $user);
        $art4 = $this->getArtifact(4, $title, $user);

        $description->method('getFullRESTValue')
            ->willReturnCallback(fn (PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getDescriptionValue($art1),
                $art2->getLastChangeset() => $this->getDescriptionValue($art2),
                $art3->getLastChangeset() => $this->getDescriptionValue($art3),
                $art4->getLastChangeset() => $this->getDescriptionValue($art4),
            });

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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

    public function testWhenDescriptionSemanticIsNotSet(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = $this->createMock(Tracker_FormElement_Field_String::class);
        $title->method('userCanRead')->willReturn(true);
        $this->semantic_title->method('getField')->willReturn($title);

        $description = $this->createMock(Tracker_FormElement_Field_Text::class);
        $description->method('userCanRead')->willReturn(true);
        $this->semantic_description->method('getField')->willReturn(null);

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = $this->getArtifact(1, $title, $user);
        $art2 = $this->getArtifact(2, $title, $user);
        $art3 = $this->getArtifact(3, $title, $user);
        $art4 = $this->getArtifact(4, $title, $user);

        $description->method('getFullRESTValue')
            ->willReturnCallback(fn (PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getDescriptionValue($art1),
                $art2->getLastChangeset() => $this->getDescriptionValue($art2),
                $art3->getLastChangeset() => $this->getDescriptionValue($art3),
                $art4->getLastChangeset() => $this->getDescriptionValue($art4),
            });

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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

    public function testFaultWhenThereIsAtLeastOneArtifactThatCurrentUserCannotRead(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = $this->createMock(Tracker_FormElement_Field_String::class);
        $title->method('userCanRead')->willReturn(true);
        $this->semantic_title->method('getField')->willReturn($title);

        $description = $this->createMock(Tracker_FormElement_Field_Text::class);
        $description->method('userCanRead')->willReturn(true);
        $this->semantic_description->method('getField')->willReturn($description);

        $dao = $this->createMock(Tracker_ArtifactDao::class);
        $dao->method('searchByIds')
            ->with([1, 2, 3, 4])
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $art1 = $this->getArtifact(1, $title, $user);
        $art2 = $this->getArtifact(2, $title, $user);
        $art3 = $this->getArtifactUserCannotView(3, $user);
        $art4 = $this->getArtifact(4, $title, $user);

        $factory = $this->createMock(Tracker_ArtifactFactory::class);
        $factory->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row['id']) {
                1 => $art1,
                2 => $art2,
                3 => $art3,
                4 => $art4,
            });

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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

        $dao = $this->createMock(Tracker_ArtifactDao::class);

        $factory = $this->createMock(Tracker_ArtifactFactory::class);

        $transformer = new RawSectionsToRepresentationTransformer(
            $dao,
            $factory,
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
