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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\ContentToInsert;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class SaveSectionDaoTest extends TestIntegrationTestCase
{
    private ArtidocWithContext $artidoc_101;
    private ArtidocWithContext $artidoc_102;

    protected function setUp(): void
    {
        $this->artidoc_101 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));
        $this->artidoc_102 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 102]));
    }

    public function testSaveSectionAtTheEnd(): void
    {
        $dao = $this->getDao();

        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1002),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1003),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1004),
        );

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1001, 1002, 1004]);
        SectionsAsserter::assertSectionsForDocument($this->artidoc_102, [1003]);
    }

    public function testSaveAlreadyExistingSectionAtTheEnd(): void
    {
        $dao = $this->getDao();

        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1002),
        );
        $dao->saveSectionAtTheEnd(
            $this->artidoc_102,
            ContentToInsert::fromArtifactId(1003),
        );

        $result = $dao->saveSectionAtTheEnd(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1001),
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(AlreadyExistingSectionWithSameArtifactFault::class, $result->error);
    }

    public function testSaveSectionBefore(): void
    {
        $dao = $this->getDao();

        [$uuid_1, $uuid_2, $uuid_3] = [
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001),
            )->unwrapOr(null),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002),
            )->unwrapOr(null),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003),
            )->unwrapOr(null),
        ];

        self::assertNotNull($uuid_1);
        self::assertNotNull($uuid_2);
        self::assertNotNull($uuid_3);

        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1004),
            $uuid_1,
        );
        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1005),
            $uuid_2,
        );
        $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1006),
            $uuid_3,
        );

        SectionsAsserter::assertSectionsForDocument($this->artidoc_101, [1004, 1001, 1005, 1002, 1006, 1003]);
    }

    public function testSaveAlreadyExistingArtifactSectionBefore(): void
    {
        $dao = $this->getDao();

        [$uuid_1] = [
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001),
            )->unwrapOr(null),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003),
            ),
        ];

        self::assertNotNull($uuid_1);

        $result = $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1003),
            $uuid_1,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(AlreadyExistingSectionWithSameArtifactFault::class, $result->error);
    }

    public function testSaveSectionBeforeUnknownSectionWillRaiseAnException(): void
    {
        $dao = $this->getDao();

        [, $uuid_2] = [
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1001),
            ),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1002),
            )->unwrapOr(null),
            $dao->saveSectionAtTheEnd(
                $this->artidoc_101,
                ContentToInsert::fromArtifactId(1003),
            ),
        ];

        self::assertNotNull($uuid_2);

        // remove section linked to artifact #1002
        $this->createArtidocSections($dao, $this->artidoc_101, $this->getArtifactIdsToInsert(1001, 1003));

        $result = $dao->saveSectionBefore(
            $this->artidoc_101,
            ContentToInsert::fromArtifactId(1004),
            $uuid_2,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToFindSiblingSectionFault::class, $result->error);
    }

    private function getDao(): SaveSectionDao
    {
        return new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());
    }

    /**
     * @return UUIDSectionIdentifierFactory
     */
    private function getSectionIdentifierFactory(): SectionIdentifierFactory
    {
        return new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }

    /**
     * @return UUIDFreetextIdentifierFactory
     */
    private function getFreetextIdentifierFactory(): FreetextIdentifierFactory
    {
        return new UUIDFreetextIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
    }

    private function createArtidocSections(SaveSectionDao $dao, ArtidocWithContext $artidoc, array $content): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_artidoc_document WHERE item_id = ?', $artidoc->document->getId());

        foreach ($content as $content_to_insert) {
            $dao->saveSectionAtTheEnd($artidoc, $content_to_insert);
        }
    }

    private function getArtifactIdsToInsert(int ...$artifact_ids): array
    {
        return array_map(
            static fn ($id) => ContentToInsert::fromArtifactId($id),
            $artifact_ids,
        );
    }
}
