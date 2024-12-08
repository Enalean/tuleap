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

namespace Tuleap\Artidoc\Document;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\RawSection;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ArtidocDaoTest extends TestIntegrationTestCase
{
    private ArtidocWithContext $artidoc_101;
    private ArtidocWithContext $artidoc_102;
    private ArtidocWithContext $artidoc_103;

    protected function setUp(): void
    {
        $this->artidoc_101 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 101]));
        $this->artidoc_102 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 102]));
        $this->artidoc_103 = new ArtidocWithContext(new ArtidocDocument(['item_id' => 103]));
    }

    public function testSearchPaginatedRawSectionsById(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insertMany('plugin_artidoc_document', [
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1001,
                'rank'        => 1,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_102->document->getId(),
                'artifact_id' => 1001,
                'rank'        => 2,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_102->document->getId(),
                'artifact_id' => 2001,
                'rank'        => 1,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1003,
                'rank'        => 3,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1002,
                'rank'        => 2,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => $this->artidoc_101->document->getId(),
                'artifact_id' => 1004,
                'rank'        => 4,
            ],
        ]);

        $dao = new ArtidocDao($identifier_factory);

        self::assertSame(4, $dao->searchPaginatedRawSections($this->artidoc_101, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1003, 1004],
            array_column($dao->searchPaginatedRawSections($this->artidoc_101, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(4, $dao->searchPaginatedRawSections($this->artidoc_101, 2, 1)->total);
        self::assertSame(
            [1002, 1003],
            array_column($dao->searchPaginatedRawSections($this->artidoc_101, 2, 1)->rows, 'artifact_id'),
        );

        self::assertSame(2, $dao->searchPaginatedRawSections($this->artidoc_102, 50, 0)->total);
        self::assertSame(
            [2001, 1001],
            array_column($dao->searchPaginatedRawSections($this->artidoc_102, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(0, $dao->searchPaginatedRawSections($this->artidoc_103, 50, 0)->total);
        self::assertSame(
            [],
            array_column($dao->searchPaginatedRawSections($this->artidoc_103, 50, 0)->rows, 'artifact_id'),
        );
    }

    private function createArtidocSections(ArtidocDao $dao, ArtidocWithContext $artidoc, array $artifact_ids): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_artidoc_document WHERE item_id = ?', $artidoc->document->getId());

        foreach ($artifact_ids as $artifact_id) {
            $dao->saveSectionAtTheEnd($artidoc, $artifact_id);
        }
    }

    public function testCloneItem(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $this->createArtidocSections($dao, $this->artidoc_101, [1001]);
        $this->createArtidocSections($dao, $this->artidoc_102, [2001, 1001]);
        $dao->saveTracker($this->artidoc_102->document->getId(), 10001);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_103, []);

        $dao->cloneItem($this->artidoc_102->document->getId(), $this->artidoc_103->document->getId());

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [2001, 1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_103, [2001, 1001]);

        self::assertSame(10001, $dao->getTracker($this->artidoc_103->document->getId()));
    }

    public function testCloneItemForEmptyDocument(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $this->createArtidocSections($dao, $this->artidoc_101, []);
        $dao->saveTracker($this->artidoc_101->document->getId(), 10001);

        $dao->cloneItem($this->artidoc_101->document->getId(), $this->artidoc_103->document->getId());

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, []);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_103, []);

        self::assertSame(10001, $dao->getTracker($this->artidoc_103->document->getId()));
    }

    public function testSave(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $this->createArtidocSections($dao, $this->artidoc_101, [1001, 1002, 1003]);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001, 1002, 1003]);

        $this->createArtidocSections($dao, $this->artidoc_102, [1001, 1003]);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001, 1002, 1003]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1001, 1003]);

        $this->createArtidocSections($dao, $this->artidoc_101, []);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, []);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1001, 1003]);
    }

    public function testSearchSectionById(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $this->createArtidocSections($dao, $this->artidoc_101, [1001, 1002, 1003]);
        $rows = $dao->searchPaginatedRawSections($this->artidoc_101, 50, 0)->rows;

        foreach ($rows as $row) {
            $dao->searchSectionById($row->id)->match(
                function (RawSection $section) use ($row) {
                    self::assertNotNull($section);
                    self::assertSame($row->id->toString(), $section->id->toString());
                    self::assertSame($row->artifact_id, $section->artifact_id);
                    self::assertSame(101, $section->item_id);
                },
                function () {
                    self::fail('Section is expected');
                },
            );
        }

        $first_section_id = $rows[0]->id;
        $this->createArtidocSections($dao, $this->artidoc_101, []);
        self::assertTrue(Result::isErr($dao->searchSectionById($first_section_id)));
    }

    public function testSaveTracker(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        self::assertNull($dao->getTracker($this->artidoc_101->document->getId()));

        $dao->saveTracker($this->artidoc_101->document->getId(), 1001);
        self::assertSame(1001, $dao->getTracker($this->artidoc_101->document->getId()));

        $dao->saveTracker($this->artidoc_101->document->getId(), 1002);
        self::assertSame(1002, $dao->getTracker($this->artidoc_101->document->getId()));
    }

    public function testSaveSectionAtTheEnd(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $dao->saveSectionAtTheEnd($this->artidoc_101, 1001);
        $dao->saveSectionAtTheEnd($this->artidoc_101, 1002);
        $dao->saveSectionAtTheEnd($this->artidoc_102, 1003);
        $dao->saveSectionAtTheEnd($this->artidoc_101, 1004);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001, 1002, 1004]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1003]);
    }

    public function testSaveAlreadyExistingSectionAtTheEnd(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $dao->saveSectionAtTheEnd($this->artidoc_101, 1001);
        $dao->saveSectionAtTheEnd($this->artidoc_101, 1002);
        $dao->saveSectionAtTheEnd($this->artidoc_102, 1003);

        $this->expectException(AlreadyExistingSectionWithSameArtifactException::class);
        $dao->saveSectionAtTheEnd($this->artidoc_101, 1001);
    }

    public function testSaveSectionBefore(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        [$uuid_1, $uuid_2, $uuid_3] = [
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1001),
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1002),
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1003),
        ];

        $dao->saveSectionBefore($this->artidoc_101, 1004, $uuid_1);
        $dao->saveSectionBefore($this->artidoc_101, 1005, $uuid_2);
        $dao->saveSectionBefore($this->artidoc_101, 1006, $uuid_3);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1004, 1001, 1005, 1002, 1006, 1003]);
    }

    public function testSaveAlreadyExistingSectionBefore(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        [$uuid_1] = [
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1001),
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1002),
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1003),
        ];

        $this->expectException(AlreadyExistingSectionWithSameArtifactException::class);
        $dao->saveSectionBefore($this->artidoc_101, 1003, $uuid_1);
    }

    public function testSaveSectionBeforeUnknownSectionWillRaiseAnException(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        [, $uuid_2] = [
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1001),
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1002),
            $dao->saveSectionAtTheEnd($this->artidoc_101, 1003),
        ];

        // remove section linked to artifact #1002
        $this->createArtidocSections($dao, $this->artidoc_101, [1001, 1003]);

        $this->expectException(UnableToFindSiblingSectionException::class);
        $dao->saveSectionBefore($this->artidoc_101, 1004, $uuid_2);
    }

    public function testDeleteSectionsByArtifactId(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $this->createArtidocSections($dao, $this->artidoc_101, [1001, 1002, 1003]);
        $this->createArtidocSections($dao, $this->artidoc_102, [1002, 1003, 1004]);
        $this->createArtidocSections($dao, $this->artidoc_103, [1003]);

        $dao->deleteSectionsByArtifactId(1003);
        $dao->deleteSectionsByArtifactId(1005);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001, 1002]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1002, 1004]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_103, []);
    }

    public function testDeleteSectionsById(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $uuid_1 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1001);
        $uuid_2 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1002);
        $uuid_3 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1003);

        $dao->deleteSectionById($uuid_2);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001, 1003]);
    }

    public function testReorderSections(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $uuid_1 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1001);
        $uuid_2 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1002);
        $uuid_3 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1003);
        $uuid_4 = $dao->saveSectionAtTheEnd($this->artidoc_102, 1001);
        $uuid_5 = $dao->saveSectionAtTheEnd($this->artidoc_102, 1002);
        $uuid_6 = $dao->saveSectionAtTheEnd($this->artidoc_102, 1003);

        $order_builder = new SectionOrderBuilder($identifier_factory);

        // "before", at the beginning
        $order = $order_builder->build([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1002, 1001, 1003]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);

        // "before", in the middle
        $order = $order_builder->build([$uuid_3->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1002, 1003, 1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);

        // "after", at the end
        $order = $order_builder->build([$uuid_2->toString()], 'after', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1003, 1001, 1002]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);

        // "after", in the middle
        $order = $order_builder->build([$uuid_3->toString()], 'after', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001, 1003, 1002]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1001, 1002, 1003]);
    }

    public function testExceptionWhenReorderSectionsOutsideOfDocument(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $uuid_1 = $dao->saveSectionAtTheEnd($this->artidoc_101, 1001);
        $uuid_2 = $dao->saveSectionAtTheEnd($this->artidoc_102, 1002);

        $order_builder = new SectionOrderBuilder($identifier_factory);

        $order = $order_builder->build([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_101,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnknownSectionToMoveFault::class, $result->error);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1002]);

        $order = $order_builder->build([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $this->artidoc_102,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToReorderSectionOutsideOfDocumentFault::class, $result->error);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_101, [1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $this->artidoc_102, [1002]);
    }

    /**
     * @param list<int> $artifact_ids
     */
    private function assertSectionsMatchArtifactIdsForDocument(
        ArtidocDao $dao,
        ArtidocWithContext $artidoc,
        array $artifact_ids,
    ): void {
        $paginated_raw_sections = $dao->searchPaginatedRawSections($artidoc, 50, 0);

        self::assertSame(count($artifact_ids), $paginated_raw_sections->total);
        self::assertSame($artifact_ids, array_column($paginated_raw_sections->rows, 'artifact_id'));
    }
}
