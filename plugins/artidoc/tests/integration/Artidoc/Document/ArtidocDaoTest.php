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

use Tuleap\Artidoc\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ArtidocDaoTest extends TestIntegrationTestCase
{
    public function testSearchPaginatedRawSectionsById(): void
    {
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());

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
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $dao->save(101, [1001]);
        $dao->save(102, [2001, 1001]);
        $dao->saveTracker(102, 10001);

        self::assertSame(0, $dao->searchPaginatedRawSectionsByItemId(103, 50, 0)->total);
        self::assertSame(
            [],
            array_column($dao->searchPaginatedRawSectionsByItemId(103, 50, 0)->rows, 'artifact_id'),
        );

        $dao->cloneItem(102, 103);

        self::assertSame(1, $dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->total);
        self::assertSame(
            [1001],
            array_column($dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(2, $dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->total);
        self::assertSame(
            [2001, 1001],
            array_column($dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(2, $dao->searchPaginatedRawSectionsByItemId(103, 50, 0)->total);
        self::assertSame(
            [2001, 1001],
            array_column($dao->searchPaginatedRawSectionsByItemId(103, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(10001, $dao->getTracker(103));
    }

    public function testSave(): void
    {
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $dao->save(101, [1001, 1002, 1003]);

        self::assertSame(3, $dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->rows, 'artifact_id'),
        );

        $dao->save(102, [1001, 1003]);

        self::assertSame(3, $dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->rows, 'artifact_id'),
        );
        self::assertSame(2, $dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->total);
        self::assertSame(
            [1001, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->rows, 'artifact_id'),
        );

        $dao->save(101, []);

        self::assertSame(0, $dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->total);
        self::assertSame(
            [],
            array_column($dao->searchPaginatedRawSectionsByItemId(101, 50, 0)->rows, 'artifact_id'),
        );
        self::assertSame(2, $dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->total);
        self::assertSame(
            [1001, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId(102, 50, 0)->rows, 'artifact_id'),
        );
    }

    public function testSearchSectionById(): void
    {
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
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
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        self::assertNull($dao->getTracker(101));

        $dao->saveTracker(101, 1001);
        self::assertSame(1001, $dao->getTracker(101));

        $dao->saveTracker(101, 1002);
        self::assertSame(1002, $dao->getTracker(101));
    }

    public function testSaveSectionAtTheEnd(): void
    {
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;
        $item_2 = 102;

        $dao->saveSectionAtTheEnd($item_1, 1001);
        $dao->saveSectionAtTheEnd($item_1, 1002);
        $dao->saveSectionAtTheEnd($item_2, 1003);
        $dao->saveSectionAtTheEnd($item_1, 1004);

        self::assertSame(3, $dao->searchPaginatedRawSectionsByItemId($item_1, 50, 0)->total);
        self::assertSame(
            [1001, 1002, 1004],
            array_column($dao->searchPaginatedRawSectionsByItemId($item_1, 50, 0)->rows, 'artifact_id'),
        );

        self::assertSame(1, $dao->searchPaginatedRawSectionsByItemId($item_2, 50, 0)->total);
        self::assertSame(
            [1003],
            array_column($dao->searchPaginatedRawSectionsByItemId($item_2, 50, 0)->rows, 'artifact_id'),
        );
    }

    public function testSaveTrackerBefore(): void
    {
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
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

        self::assertSame(6, $dao->searchPaginatedRawSectionsByItemId($item_1, 50, 0)->total);
        self::assertSame(
            [1004, 1001, 1005, 1002, 1006, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId($item_1, 50, 0)->rows, 'artifact_id'),
        );
    }

    public function testSaveTrackerBeforeUnknownSectionWillPutItAtTheBeginningUntilWeFindABetterSolution(): void
    {
        $identifier_factory = new SectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);

        $item_1 = 101;

        [, $uuid_2,] = [
            $dao->saveSectionAtTheEnd($item_1, 1001),
            $dao->saveSectionAtTheEnd($item_1, 1002),
            $dao->saveSectionAtTheEnd($item_1, 1003),
        ];

        // remove section linked to artifact #1002
        $dao->save(101, [1001, 1003]);

        $dao->saveSectionBefore($item_1, 1004, $uuid_2);

        self::assertSame(3, $dao->searchPaginatedRawSectionsByItemId($item_1, 50, 0)->total);
        self::assertSame(
            [1004, 1001, 1003],
            array_column($dao->searchPaginatedRawSectionsByItemId($item_1, 50, 0)->rows, 'artifact_id'),
        );
    }
}
