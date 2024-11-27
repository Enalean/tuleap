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

use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactException;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionException;
use Tuleap\DB\DBFactory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ArtidocDaoTest extends TestIntegrationTestCase
{
    public function testSearchPaginatedRawSectionsById(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insertMany('plugin_artidoc_document', [
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => 101,
                'artifact_id' => 1001,
                'rank'        => 1,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => 102,
                'artifact_id' => 1001,
                'rank'        => 2,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => 102,
                'artifact_id' => 2001,
                'rank'        => 1,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => 101,
                'artifact_id' => 1003,
                'rank'        => 3,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => 101,
                'artifact_id' => 1002,
                'rank'        => 2,
            ],
            [
                'id'          => $identifier_factory->buildIdentifier()->getBytes(),
                'item_id'     => 101,
                'artifact_id' => 1004,
                'rank'        => 4,
            ],
        ]);

        $dao = new ArtidocDao($identifier_factory);

        self::assertSame(4, $dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1003, 1004],
            array_column($dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(4, $dao->searchPaginatedRawSectionsByItemId(101, 2, 1)->total);
        self::assertSame(
            [1002, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId(101, 2, 1)->rows, 'artifact_id'),
        );

        self::assertSame(2, $dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->total);
        self::assertSame(
            [2001, 1001],
            array_column($dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(0, $dao->searchPaginatedRawSectionsByItemId(103, 50, 0)->total);
        self::assertSame(
            [],
            array_column($dao->searchPaginatedRawSectionsByItemId(103, 50, 0)->rows, 'artifact_id'),
        );
    }

    public function testCloneItem(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $dao->save(101, [1001]);
        $dao->save(102, [2001, 1001]);
        $dao->saveTracker(102, 10001);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 103, []);

        $dao->cloneItem(102, 103);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 101, [1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 102, [2001, 1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 103, [2001, 1001]);

        self::assertSame(10001, $dao->getTracker(103));
    }

    public function testCloneItemForEmptyDocument(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $dao->save(101, []);
        $dao->saveTracker(101, 10001);


        $dao->cloneItem(101, 103);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 101, []);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 103, []);

        self::assertSame(10001, $dao->getTracker(103));
    }

    public function testSave(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $dao->save(101, [1001, 1002, 1003]);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 101, [1001, 1002, 1003]);

        $dao->save(102, [1001, 1003]);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 101, [1001, 1002, 1003]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 102, [1001, 1003]);

        $dao->save(101, []);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 101, []);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 102, [1001, 1003]);
    }

    public function testSearchSectionById(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $dao->save(101, [1001, 1002, 1003]);
        $rows = $dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->rows;

        foreach ($rows as $row) {
            $section = $dao->searchSectionById($row->id);
            self::assertNotNull($section);
            self::assertSame($row->id->toString(), $section->id->toString());
            self::assertSame($row->artifact_id, $section->artifact_id);
            self::assertSame(101, $section->item_id);
        }

        $first_section_id = $rows[0]->id;
        $dao->save(101, []);
        self::assertNull($dao->searchSectionById($first_section_id));
    }

    public function testSaveTracker(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        self::assertNull($dao->getTracker(101));

        $dao->saveTracker(101, 1001);
        self::assertSame(1001, $dao->getTracker(101));

        $dao->saveTracker(101, 1002);
        self::assertSame(1002, $dao->getTracker(101));
    }

    public function testSaveSectionAtTheEnd(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;
        $item_2 = 102;

        $dao->saveSectionAtTheEnd($item_1, 1001);
        $dao->saveSectionAtTheEnd($item_1, 1002);
        $dao->saveSectionAtTheEnd($item_2, 1003);
        $dao->saveSectionAtTheEnd($item_1, 1004);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1001, 1002, 1004]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1003]);
    }

    public function testSaveAlreadyExistingSectionAtTheEnd(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;
        $item_2 = 102;

        $dao->saveSectionAtTheEnd($item_1, 1001);
        $dao->saveSectionAtTheEnd($item_1, 1002);
        $dao->saveSectionAtTheEnd($item_2, 1003);

        $this->expectException(AlreadyExistingSectionWithSameArtifactException::class);
        $dao->saveSectionAtTheEnd($item_1, 1001);
    }

    public function testSaveSectionBefore(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;


        [$uuid_1, $uuid_2, $uuid_3] = [
            $dao->saveSectionAtTheEnd($item_1, 1001),
            $dao->saveSectionAtTheEnd($item_1, 1002),
            $dao->saveSectionAtTheEnd($item_1, 1003),
        ];

        $dao->saveSectionBefore($item_1, 1004, $uuid_1);
        $dao->saveSectionBefore($item_1, 1005, $uuid_2);
        $dao->saveSectionBefore($item_1, 1006, $uuid_3);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1004, 1001, 1005, 1002, 1006, 1003]);
    }

    public function testSaveAlreadyExistingSectionBefore(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;


        [$uuid_1] = [
            $dao->saveSectionAtTheEnd($item_1, 1001),
            $dao->saveSectionAtTheEnd($item_1, 1002),
            $dao->saveSectionAtTheEnd($item_1, 1003),
        ];

        $this->expectException(AlreadyExistingSectionWithSameArtifactException::class);
        $dao->saveSectionBefore($item_1, 1003, $uuid_1);
    }

    public function testSaveSectionBeforeUnknownSectionWillRaiseAnException(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;

        [, $uuid_2] = [
            $dao->saveSectionAtTheEnd($item_1, 1001),
            $dao->saveSectionAtTheEnd($item_1, 1002),
            $dao->saveSectionAtTheEnd($item_1, 1003),
        ];

        // remove section linked to artifact #1002
        $dao->save(101, [1001, 1003]);

        $this->expectException(UnableToFindSiblingSectionException::class);
        $dao->saveSectionBefore($item_1, 1004, $uuid_2);
    }

    public function testDeleteSectionsByArtifactId(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $dao->save(101, [1001, 1002, 1003]);
        $dao->save(102, [1002, 1003, 1004]);
        $dao->save(103, [1003]);

        $dao->deleteSectionsByArtifactId(1003);
        $dao->deleteSectionsByArtifactId(1005);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, 101, [1001, 1002]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 102, [1002, 1004]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, 103, []);
    }

    public function testDeleteSectionsById(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;

        $uuid_1 = $dao->saveSectionAtTheEnd($item_1, 1001);
        $uuid_2 = $dao->saveSectionAtTheEnd($item_1, 1002);
        $uuid_3 = $dao->saveSectionAtTheEnd($item_1, 1003);

        $dao->deleteSectionById($uuid_2);

        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1001, 1003]);
    }

    public function testReorderSections(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;
        $item_2 = 102;

        $uuid_1 = $dao->saveSectionAtTheEnd($item_1, 1001);
        $uuid_2 = $dao->saveSectionAtTheEnd($item_1, 1002);
        $uuid_3 = $dao->saveSectionAtTheEnd($item_1, 1003);
        $uuid_4 = $dao->saveSectionAtTheEnd($item_2, 1001);
        $uuid_5 = $dao->saveSectionAtTheEnd($item_2, 1002);
        $uuid_6 = $dao->saveSectionAtTheEnd($item_2, 1003);

        $order_builder = new SectionOrderBuilder($identifier_factory);

        // "before", at the beginning
        $order = $order_builder->buildFromRest([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $item_1,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1002, 1001, 1003]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1001, 1002, 1003]);

        // "before", in the middle
        $order = $order_builder->buildFromRest([$uuid_3->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $item_1,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1002, 1003, 1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1001, 1002, 1003]);

        // "after", at the end
        $order = $order_builder->buildFromRest([$uuid_2->toString()], 'after', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $item_1,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1003, 1001, 1002]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1001, 1002, 1003]);

        // "after", in the middle
        $order = $order_builder->buildFromRest([$uuid_3->toString()], 'after', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $item_1,
            $order->value,
        );
        self::assertTrue(Result::isOk($result));
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1001, 1003, 1002]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1001, 1002, 1003]);
    }

    public function testExceptionWhenReorderSectionsOutsideOfDocument(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;
        $item_2 = 102;

        $uuid_1 = $dao->saveSectionAtTheEnd($item_1, 1001);
        $uuid_2 = $dao->saveSectionAtTheEnd($item_2, 1002);

        $order_builder = new SectionOrderBuilder($identifier_factory);

        $order = $order_builder->buildFromRest([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $item_1,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnknownSectionToMoveFault::class, $result->error);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1002]);

        $order = $order_builder->buildFromRest([$uuid_2->toString()], 'before', $uuid_1->toString());
        self::assertTrue(Result::isOk($order));
        $result = $dao->reorder(
            $item_2,
            $order->value,
        );
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UnableToReorderSectionOutsideOfDocumentFault::class, $result->error);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_1, [1001]);
        $this->assertSectionsMatchArtifactIdsForDocument($dao, $item_2, [1002]);
    }

    /**
     * @param list<int> $artifact_ids
     */
    private function assertSectionsMatchArtifactIdsForDocument(
        ArtidocDao $dao,
        int $document_id,
        array $artifact_ids,
    ): void {
        $paginated_raw_sections = $dao->searchPaginatedRawSectionsByItemId($document_id, 50, 0);

        self::assertSame(count($artifact_ids), $paginated_raw_sections->total);
        self::assertSame($artifact_ids, array_column($paginated_raw_sections->rows, 'artifact_id'));
    }
}
