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

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use org\bovigo\vfs\vfsStream;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SearchUploadStub;
use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBConnection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\CannotWriteFileException;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocFileBeingUploadedWriterTest extends TestCase
{
    use ForgeConfigSandbox;

    private const ARTIDOC_ID = 123;

    public function testWriteChunk(): void
    {
        $data_dir = vfsStream::setup()->url();
        \ForgeConfig::set('sys_data_dir', $data_dir);

        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $db_connection = $this->createMock(DBConnection::class);
        $writer        = new ArtidocFileBeingUploadedWriter(
            SearchUploadStub::withFile(
                new UploadFileInformation(
                    self::ARTIDOC_ID,
                    $file_information->getID(),
                    $file_information->getName(),
                    $file_information->getLength(),
                ),
            ),
            $db_connection,
        );

        $db_connection->expects(self::exactly(2))->method('reconnectAfterALongRunningProcess');

        $content      = 'Body content';
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, $content[0]);
        rewind($input_stream);
        $written_size = $writer->writeChunk($file_information, 0, $input_stream);
        fclose($input_stream);
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, substr($content, 1));
        rewind($input_stream);
        $written_size += $writer->writeChunk($file_information, 1, $input_stream);
        fclose($input_stream);

        $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));
        self::assertSame(
            $content,
            file_get_contents($path_allocator->getPathForItemBeingUploaded($file_information))
        );
        self::assertSame($written_size, strlen($content));
    }

    public function testCanNotWriteMoreThanTheFileSize(): void
    {
        $data_dir = vfsStream::setup()->url();
        \ForgeConfig::set('sys_data_dir', $data_dir);

        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $db_connection = $this->createMock(DBConnection::class);
        $writer        = new ArtidocFileBeingUploadedWriter(
            SearchUploadStub::withFile(
                new UploadFileInformation(
                    self::ARTIDOC_ID,
                    $file_information->getID(),
                    $file_information->getName(),
                    $file_information->getLength(),
                ),
            ),
            $db_connection,
        );

        $db_connection->expects(self::once())->method('reconnectAfterALongRunningProcess');

        $content      = str_repeat('A', $file_information->getLength() * 2);
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, $content);
        rewind($input_stream);
        $written_size = $writer->writeChunk($file_information, 0, $input_stream);
        fclose($input_stream);

        $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));
        self::assertSame(
            str_repeat('A', $file_information->getLength()),
            file_get_contents($path_allocator->getPathForItemBeingUploaded($file_information))
        );
        self::assertSame($written_size, $file_information->getLength());
    }

    public function testExceptionWhenFileNotFound(): void
    {
        $data_dir = vfsStream::setup()->url();
        \ForgeConfig::set('sys_data_dir', $data_dir);

        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $db_connection = $this->createMock(DBConnection::class);
        $writer        = new ArtidocFileBeingUploadedWriter(
            SearchUploadStub::withoutFile(),
            $db_connection,
        );

        $this->expectException(CannotWriteFileException::class);

        $content      = 'Body content';
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, $content);
        rewind($input_stream);
        try {
            $writer->writeChunk($file_information, 0, $input_stream);
        } finally {
            fclose($input_stream);

            $path_allocator = ArtidocUploadPathAllocator::fromArtidoc(new ArtidocDocument(['item_id' => self::ARTIDOC_ID]));
            self::assertFileDoesNotExist($path_allocator->getPathForItemBeingUploaded($file_information));
        }
    }
}
