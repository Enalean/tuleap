<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBConnection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Tus\TusFileInformation;

class FileBeingUploadedWriterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testWriteChunk(): void
    {
        $tmp_dir = vfsStream::setup()->url();
        \ForgeConfig::set('tmp_dir', $tmp_dir);

        $path_allocator = \Mockery::mock(PathAllocator::class);
        $path_allocator
            ->shouldReceive('getPathForItemBeingUploaded')
            ->andReturn("$tmp_dir/12");
        $db_connection  = \Mockery::mock(DBConnection::class);
        $writer         = new FileBeingUploadedWriter($path_allocator, $db_connection);

        $db_connection->shouldReceive('reconnectAfterALongRunningProcess')->twice();

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

        $this->assertSame(
            $content,
            file_get_contents($path_allocator->getPathForItemBeingUploaded($file_information))
        );
        $this->assertSame($written_size, strlen($content));
    }

    public function testCanNotWriteMoreThanTheFileSize(): void
    {
        $tmp_dir = vfsStream::setup()->url();
        \ForgeConfig::set('tmp_dir', $tmp_dir);

        $path_allocator = \Mockery::mock(PathAllocator::class);
        $path_allocator
            ->shouldReceive('getPathForItemBeingUploaded')
            ->andReturn("$tmp_dir/12");
        $db_connection  = \Mockery::mock(DBConnection::class);
        $writer         = new FileBeingUploadedWriter($path_allocator, $db_connection);

        $db_connection->shouldReceive('reconnectAfterALongRunningProcess')->once();

        $item_id          = 12;
        $file_length      = 123;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', $file_length, 0);

        $content      = str_repeat('A', $file_length * 2);
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, $content);
        rewind($input_stream);
        $written_size = $writer->writeChunk($file_information, 0, $input_stream);
        fclose($input_stream);

        $this->assertSame(
            str_repeat('A', $file_length),
            file_get_contents($path_allocator->getPathForItemBeingUploaded($file_information))
        );
        $this->assertSame($written_size, $file_length);
    }

    public function testInputThatIsNotAResourceIsRejected(): void
    {
        $writer = new FileBeingUploadedWriter(\Mockery::mock(PathAllocator::class), \Mockery::mock(DBConnection::class));

        $this->expectException(\InvalidArgumentException::class);

        $not_a_resource = false;
        $writer->writeChunk(\Mockery::mock(TusFileInformation::class), 0, $not_a_resource);
    }
}
