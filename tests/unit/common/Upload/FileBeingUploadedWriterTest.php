<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Upload;

use org\bovigo\vfs\vfsStream;
use Tuleap\DB\DBConnection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Tus\TusFileInformation;

final class FileBeingUploadedWriterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testWriteChunk(): void
    {
        $tmp_dir = vfsStream::setup()->url();
        \ForgeConfig::set('tmp_dir', $tmp_dir);

        $path_allocator = $this->createMock(PathAllocator::class);
        $path_allocator
            ->method('getPathForItemBeingUploaded')
            ->willReturn("$tmp_dir/12");
        $db_connection = $this->createMock(DBConnection::class);
        $writer        = new FileBeingUploadedWriter($path_allocator, $db_connection);

        $db_connection->expects(self::exactly(2))->method('reconnectAfterALongRunningProcess');

        $item_id          = 12;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', 123, 0);

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

        self::assertSame(
            $content,
            file_get_contents($path_allocator->getPathForItemBeingUploaded($file_information))
        );
        self::assertSame($written_size, strlen($content));
    }

    public function testCanNotWriteMoreThanTheFileSize(): void
    {
        $tmp_dir = vfsStream::setup()->url();
        \ForgeConfig::set('tmp_dir', $tmp_dir);

        $path_allocator = $this->createMock(PathAllocator::class);
        $path_allocator
            ->method('getPathForItemBeingUploaded')
            ->willReturn("$tmp_dir/12");
        $db_connection = $this->createMock(DBConnection::class);
        $writer        = new FileBeingUploadedWriter($path_allocator, $db_connection);

        $db_connection->expects(self::once())->method('reconnectAfterALongRunningProcess');

        $item_id          = 12;
        $file_length      = 123;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', $file_length, 0);

        $content      = str_repeat('A', $file_length * 2);
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, $content);
        rewind($input_stream);
        $written_size = $writer->writeChunk($file_information, 0, $input_stream);
        fclose($input_stream);

        self::assertSame(
            str_repeat('A', $file_length),
            file_get_contents($path_allocator->getPathForItemBeingUploaded($file_information))
        );
        self::assertSame($written_size, $file_length);
    }

    public function testInputThatIsNotAResourceIsRejected(): void
    {
        $writer = new FileBeingUploadedWriter($this->createMock(PathAllocator::class), $this->createMock(DBConnection::class));

        $this->expectException(\InvalidArgumentException::class);

        $not_a_resource = false;
        $writer->writeChunk($this->createMock(TusFileInformation::class), 0, $not_a_resource);
    }
}
