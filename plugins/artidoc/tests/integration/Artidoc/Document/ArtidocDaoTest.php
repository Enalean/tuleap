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

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ArtidocDaoTest extends TestIntegrationTestCase
{
    public function testSearchPaginatedRawSectionsById(): void
    {
        $uuid_factory = new \Tuleap\DB\DatabaseUUIDV7Factory();

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->insertMany('plugin_artidoc_document', [
            [
                'id'          => $uuid_factory->buildUUIDBytes(),
                'item_id'     => 101,
                'artifact_id' => 1001,
                'rank'        => 1,
            ],
            [
                'id'          => $uuid_factory->buildUUIDBytes(),
                'item_id'     => 102,
                'artifact_id' => 1001,
                'rank'        => 2,
            ],
            [
                'id'          => $uuid_factory->buildUUIDBytes(),
                'item_id'     => 102,
                'artifact_id' => 2001,
                'rank'        => 1,
            ],
            [
                'id'          => $uuid_factory->buildUUIDBytes(),
                'item_id'     => 101,
                'artifact_id' => 1003,
                'rank'        => 3,
            ],
            [
                'id'          => $uuid_factory->buildUUIDBytes(),
                'item_id'     => 101,
                'artifact_id' => 1002,
                'rank'        => 2,
            ],
            [
                'id'          => $uuid_factory->buildUUIDBytes(),
                'item_id'     => 101,
                'artifact_id' => 1004,
                'rank'        => 4,
            ],
        ]);

        $dao = new ArtidocDao();

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
        $dao = new ArtidocDao();
        $dao->save(101, [1001]);
        $dao->save(102, [2001, 1001]);

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
    }

    public function testSave(): void
    {
        $dao = new ArtidocDao();
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
}
