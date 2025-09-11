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
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OngoingUploadDaoTest extends TestIntegrationTestCase
{
    private const int USER_ID = 101;

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

        $uploaded_file = $dao->searchUpload($id);
        self::assertTrue(Result::isOk($uploaded_file));
        self::assertSame($file_to_upload->artidoc_id, $uploaded_file->value->artidoc_id);
        self::assertSame($file_to_upload->name, $uploaded_file->value->getName());
        self::assertSame($file_to_upload->size, $uploaded_file->value->getLength());

        $dao->deleteById($id);

        $uploaded_file = $dao->searchUpload($id);
        self::assertTrue(Result::isErr($uploaded_file));
    }

    public function testDeleteExpiredFiles(): void
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

        self::assertTrue(Result::isOk($dao->searchUpload($id)));

        self::assertEmpty($dao->searchExpiredUploads($now));
        $dao->deleteExpiredFiles($now);

        self::assertTrue(Result::isOk($dao->searchUpload($id)));

        self::assertNotEmpty($dao->searchExpiredUploads($time_to_cleanup));
        $dao->deleteExpiredFiles($time_to_cleanup);

        self::assertTrue(Result::isErr($dao->searchUpload($id)));
        self::assertEmpty($dao->searchExpiredUploads($time_to_cleanup));
    }

    public function testRemoveExpirationDate(): void
    {
        $now              = new \DateTimeImmutable();
        $expiration       = $now->add(new \DateInterval('P1D'));
        $after_expiration = $expiration->add(new \DateInterval('P1D'));

        $dao = new OngoingUploadDao(
            new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $empty_to_upload = InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'filename.png',
            0,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            null,
        );
        $file_to_upload  = InsertFileToUpload::fromComponents(
            new ArtidocDocument(['item_id' => 101]),
            'filename.png',
            123,
            UserTestBuilder::aUser()->withId(self::USER_ID)->build(),
            $expiration,
        );

        $empty_id = $dao->saveFileOnGoingUpload($empty_to_upload);
        $file_id  = $dao->saveFileOnGoingUpload($file_to_upload);

        self::assertTrue(Result::isOk($dao->searchUpload($empty_id)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($empty_id, self::USER_ID, $now)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($empty_id, self::USER_ID, $after_expiration)));

        self::assertTrue(Result::isOk($dao->searchUpload($file_id)));
        self::assertTrue(Result::isOk($dao->searchNotExpiredOngoingUpload($file_id, self::USER_ID, $now)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($file_id, self::USER_ID, $after_expiration)));

        $dao->removeExpirationDate($empty_id);
        $dao->removeExpirationDate($file_id);

        self::assertTrue(Result::isOk($dao->searchUpload($empty_id)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($empty_id, self::USER_ID, $now)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($empty_id, self::USER_ID, $after_expiration)));

        self::assertTrue(Result::isOk($dao->searchUpload($file_id)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($file_id, self::USER_ID, $now)));
        self::assertTrue(Result::isErr($dao->searchNotExpiredOngoingUpload($file_id, self::USER_ID, $after_expiration)));
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
