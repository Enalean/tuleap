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

namespace integration\Artidoc\Upload\Section\File;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Upload\Section\File\InsertFileToUpload;
use Tuleap\Artidoc\Upload\Section\File\OngoingUploadDao;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

final class OngoingUploadDaoTest extends TestIntegrationTestCase
{
    private const USER_ID = 101;

    public function testSave(): void
    {
        $dao = new OngoingUploadDao(
            new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $file_to_upload = InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'filename.png',
            123,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            new \DateTimeImmutable(),
        );

        $id = $dao->saveFileOnGoingUpload($file_to_upload);

        $row = $dao->searchFileOngoingUploadById($id);
        self::assertNotNull($row);
        self::assertSame($file_to_upload->artidoc_id, $row['item_id']);
        self::assertSame($file_to_upload->name, $row['file_name']);
        self::assertSame($file_to_upload->size, $row['file_size']);
        self::assertSame($file_to_upload->user_id, $row['user_id']);

        $dao->deleteById($id);

        $row = $dao->searchFileOngoingUploadById($id);
        self::assertNull($row);
    }

    public function testDeleteUnusableFile(): void
    {
        $now             = new \DateTimeImmutable();
        $expiration      = $now->add(new \DateInterval('P1D'));
        $time_to_cleanup = $expiration->add(new \DateInterval('P2D'));

        $dao = new OngoingUploadDao(
            new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $file_to_upload = InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'filename.png',
            123,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            $expiration,
        );

        $id = $dao->saveFileOnGoingUpload($file_to_upload);

        $row = $dao->searchFileOngoingUploadById($id);
        self::assertNotNull($row);

        $dao->deleteUnusableFile($now);

        $row = $dao->searchFileOngoingUploadById($id);
        self::assertNotNull($row);

        $dao->deleteUnusableFile($time_to_cleanup);

        $row = $dao->searchFileOngoingUploadById($id);
        self::assertNull($row);
    }

    public function testSearchFileOngoingUploadByItemIdNameAndExpirationDate(): void
    {
        $now        = new \DateTimeImmutable();
        $expiration = $now->add(new \DateInterval('P1D'));

        $dao = new OngoingUploadDao(
            new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $dao->saveFileOnGoingUpload(InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'filename.png',
            123,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            $expiration,
        ));
        $dao->saveFileOnGoingUpload(InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'filename.png',
            456,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            $expiration,
        ));
        $dao->saveFileOnGoingUpload(InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'other.png',
            456,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            $expiration,
        ));

        $rows = $dao->searchFileOngoingUploadByItemIdNameAndExpirationDate(101, 'filename.png', $now);
        self::assertCount(2, $rows);

        $rows = $dao->searchFileOngoingUploadByItemIdNameAndExpirationDate(101, 'filename.png', $expiration);
        self::assertCount(0, $rows);
    }
}
