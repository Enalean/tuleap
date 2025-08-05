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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Upload\Section\File\CreateFileToUploadStub;
use Tuleap\Artidoc\Stubs\Upload\Section\File\FinishEmptyFileToUploadStub;
use Tuleap\Artidoc\Upload\Section\File\CannotWriteFileFault;
use Tuleap\Artidoc\Upload\Section\File\UploadCreationConflictFault;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileCreatorTest extends TestCase
{
    public function testItCreatesANewFileUpload(): void
    {
        $identifier = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $creator = new FileCreator(
            CreateFileToUploadStub::withSuccessfulCreation($identifier),
            FinishEmptyFileToUploadStub::shouldNotBeCalled(),
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
            new FilePOSTRepresentation(1, 'filename.png', 123, 'image/png'),
            new \DateTimeImmutable(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame($identifier->toString(), $result->value->id);
        self::assertSame('/uploads/artidoc/sections/file/' . $identifier->toString(), $result->value->upload_href);
        self::assertSame('/artidoc/attachments/' . $identifier->toString() . '-filename.png', $result->value->download_href);
    }

    public function testWhenFileSizeIsZeroItCreatesAnEmptyFile(): void
    {
        $identifier = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $creator = new FileCreator(
            CreateFileToUploadStub::withSuccessfulCreation($identifier),
            FinishEmptyFileToUploadStub::withSuccessfulCreation(),
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
            new FilePOSTRepresentation(1, 'empty.png', 0, 'image/png'),
            new \DateTimeImmutable(),
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame($identifier->toString(), $result->value->id);
        self::assertSame('/artidoc/attachments/' . $identifier->toString() . '-empty.png', $result->value->download_href);
    }

    public function testWhenEmptyFileFails(): void
    {
        $identifier = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $creator = new FileCreator(
            CreateFileToUploadStub::withSuccessfulCreation($identifier),
            FinishEmptyFileToUploadStub::withFailedCreation(),
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
            new FilePOSTRepresentation(1, 'empty.png', 0, 'image/png'),
            new \DateTimeImmutable(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotWriteFileFault::class, $result->error);
    }

    public function testWhenUploadCreationConflictItFaults(): void
    {
        $creator = new FileCreator(
            CreateFileToUploadStub::withCreationConflict(),
            FinishEmptyFileToUploadStub::shouldNotBeCalled(),
        );

        $result = $creator->create(
            new ArtidocDocument(['item_id' => 1]),
            UserTestBuilder::buildWithDefaults(),
            new FilePOSTRepresentation(1, 'filename.png', 123, 'image/png'),
            new \DateTimeImmutable(),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UploadCreationConflictFault::class, $result->error);
    }
}
