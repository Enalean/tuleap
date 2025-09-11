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

namespace Tuleap\Artidoc\Upload\Section\File;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SaveFileUploadStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileToUploadCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int UPLOADING_USER_ID = 102;
    private const int FILE_SIZE         = 123;
    private const int MAX_SIZE_UPLOAD   = 1000;

    private OngoingUploadDao&MockObject $dao;
    private int $file_size;

    #[\Override]
    protected function setUp(): void
    {
        $this->file_size = self::FILE_SIZE;

        $this->dao = $this->createMock(OngoingUploadDao::class);
    }

    public function testItCreatesAnUploadWithA4HoursExpirationDate(): void
    {
        $identifier = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $save = SaveFileUploadStub::withCreatedIdentifier($identifier);

        $this->dao->method('searchFileOngoingUploadByItemIdNameAndExpirationDate')->willReturn([]);

        $creator = new FileToUploadCreator(
            $this->dao,
            $save,
            new DBTransactionExecutorPassthrough(),
            self::MAX_SIZE_UPLOAD
        );

        $current_time = new \DateTimeImmutable();
        $result       = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build(),
            $current_time,
            'filename.txt',
            $this->file_size,
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame('/uploads/artidoc/sections/file/' . $identifier->toString(), $result->value->getUploadHref());
        self::assertTrue($save->isCalled());
        self::assertSame(
            $current_time->add(new \DateInterval('PT4H'))->getTimestamp(),
            $save->getSaved()->expiration_date,
        );
    }

    public function testItCreatesAnUploadWithoutExpirationDateIfFileIsEmpty(): void
    {
        $identifier = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $save = SaveFileUploadStub::withCreatedIdentifier($identifier);

        $this->dao->method('searchFileOngoingUploadByItemIdNameAndExpirationDate')->willReturn([]);

        $creator = new FileToUploadCreator(
            $this->dao,
            $save,
            new DBTransactionExecutorPassthrough(),
            self::MAX_SIZE_UPLOAD
        );

        $current_time = new \DateTimeImmutable();
        $result       = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build(),
            $current_time,
            'filename.txt',
            0,
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame('/uploads/artidoc/sections/file/' . $identifier->toString(), $result->value->getUploadHref());
        self::assertTrue($save->isCalled());
        self::assertNull($save->getSaved()->expiration_date);
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile(): void
    {
        $identifier = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $this->dao->method('searchFileOngoingUploadByItemIdNameAndExpirationDate')->willReturn([
            ['id' => $identifier, 'user_id' => self::UPLOADING_USER_ID, 'file_size' => self::FILE_SIZE],
        ]);
        $save = SaveFileUploadStub::withCreatedIdentifier($identifier);

        $creator = new FileToUploadCreator(
            $this->dao,
            $save,
            new DBTransactionExecutorPassthrough(),
            self::MAX_SIZE_UPLOAD
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build(),
            new \DateTimeImmutable(),
            'filename.txt',
            $this->file_size,
        );

        self::assertFalse($save->isCalled());
        self::assertTrue(Result::isOk($result));
        self::assertSame('/uploads/artidoc/sections/file/' . $identifier->toString(), $result->value->getUploadHref());
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit(): void
    {
        $this->file_size = self::MAX_SIZE_UPLOAD + 1;

        $creator = new FileToUploadCreator(
            $this->dao,
            SaveFileUploadStub::shouldNotBeCalled(),
            new DBTransactionExecutorPassthrough(),
            self::MAX_SIZE_UPLOAD
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build(),
            new \DateTimeImmutable(),
            'filename.txt',
            $this->file_size,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UploadMaxSizeExceededFault::class, $result->error);
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument(): void
    {
        $this->dao->method('searchFileOngoingUploadByItemIdNameAndExpirationDate')->willReturn([
            ['user_id' => 103, 'file_size' => self::FILE_SIZE],
        ]);

        $creator = new FileToUploadCreator(
            $this->dao,
            SaveFileUploadStub::shouldNotBeCalled(),
            new DBTransactionExecutorPassthrough(),
            self::MAX_SIZE_UPLOAD
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::aUser()->withId(self::UPLOADING_USER_ID)->build(),
            new \DateTimeImmutable(),
            'filename.txt',
            $this->file_size,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UploadCreationConflictFault::class, $result->error);
    }
}
