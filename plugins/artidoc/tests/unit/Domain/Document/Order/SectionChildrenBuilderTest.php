<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Domain\Document\Order;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\SearchAllSectionsStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SectionChildrenBuilderTest extends TestCase
{
    private const ARTIDOC_ID = 123;
    private ArtidocWithContext $artidoc;
    private SearchAllSectionsStub $sections;
    private RetrievedSection $section_A;
    private RetrievedSection $section_AA;
    private RetrievedSection $section_AAA;
    private RetrievedSection $section_B;
    private RetrievedSection $section_BB;
    private RetrievedSection $section_BBB;
    private RetrievedSection $section_C;

    #[\Override]
    protected function setUp(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $this->artidoc      = new ArtidocWithContext(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));

        $this->section_A   = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::One->value,
                'item_id'     => 1,
                'artifact_id' => 201,
                'rank'        => 1,
            ]
        );
        $this->section_AA  = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::Two->value,
                'item_id'     => 1,
                'artifact_id' => 202,
                'rank'        => 2,
            ]
        );
        $this->section_AAA = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::Three->value,
                'item_id'     => 1,
                'artifact_id' => 203,
                'rank'        => 3,
            ]
        );
        $this->section_B   = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::One->value,
                'item_id'     => 1,
                'artifact_id' => 204,
                'rank'        => 4,
            ]
        );
        $this->section_BBB = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::Three->value,
                'item_id'     => 1,
                'artifact_id' => 205,
                'rank'        => 5,
            ]
        );
        $this->section_BB  = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::Two->value,
                'item_id'     => 1,
                'artifact_id' => 206,
                'rank'        => 6,
            ]
        );
        $this->section_C   = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::One->value,
                'item_id'     => 1,
                'artifact_id' => 207,
                'rank'        => 7,
            ]
        );

        $this->sections = SearchAllSectionsStub::withSections([
            $this->section_A,
            $this->section_AA,
            $this->section_AAA,
            $this->section_B,
            $this->section_BBB,
            $this->section_BB,
            $this->section_C,
        ]);
    }

    public function getBuilder(): SectionChildrenBuilder
    {
        return new SectionChildrenBuilder($this->sections);
    }

    public function testSectionAShouldHaveTwoChildren(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_A->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([$this->section_AA->id, $this->section_AAA->id], $result->value);
    }

    public function testSectionAAShouldHaveOneChild(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_AA->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([$this->section_AAA->id], $result->value);
    }

    public function testSectionAAAShouldNotHaveChildren(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_AAA->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([], $result->value);
    }

    public function testSectionBShouldHaveTwoChildren(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_B->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([$this->section_BBB->id, $this->section_BB->id], $result->value);
    }

    public function testSectionBBBShouldNotHaveChildren(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_BBB->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([], $result->value);
    }

    public function testSectionBBShouldNotHaveChildren(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_BB->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([], $result->value);
    }

    public function testSectionCShouldNotHaveChildren(): void
    {
        $result = $this->getBuilder()->getSectionChildren($this->section_C->id, $this->artidoc);

        self::assertTrue(Result::isOk($result));
        self::assertEquals([], $result->value);
    }
}
