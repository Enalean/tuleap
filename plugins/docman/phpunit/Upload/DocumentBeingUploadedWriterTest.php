<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Tus\TusFileInformation;
use Tuleap\ForgeConfigSandbox;

class DocumentBeingUploadedWriterTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    public function testWriteChunk() : void
    {
        \ForgeConfig::set('tmp_dir', vfsStream::setup()->url());

        $path_allocator = new DocumentUploadPathAllocator();
        $writer         = new DocumentBeingUploadedWriter($path_allocator);

        $item_id          = 12;
        $file_information = new DocumentBeingUploadedInformation($item_id, 123, 0);

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
            file_get_contents($path_allocator->getPathForItemBeingUploaded($item_id))
        );
        $this->assertSame($written_size, strlen($content));
    }

    public function testCanNotWriteMoreThanTheFileSize() : void
    {
        \ForgeConfig::set('tmp_dir', vfsStream::setup()->url());

        $path_allocator = new DocumentUploadPathAllocator();
        $writer         = new DocumentBeingUploadedWriter($path_allocator);

        $item_id          = 12;
        $file_length      = 123;
        $file_information = new DocumentBeingUploadedInformation($item_id, $file_length, 0);

        $content      = str_repeat('A', $file_length * 2);
        $input_stream = fopen('php://memory', 'rb+');
        fwrite($input_stream, $content);
        rewind($input_stream);
        $written_size = $writer->writeChunk($file_information, 0, $input_stream);
        fclose($input_stream);

        $this->assertSame(
            str_repeat('A', $file_length),
            file_get_contents($path_allocator->getPathForItemBeingUploaded($item_id))
        );
        $this->assertSame($written_size, $file_length);
    }

    public function testInputThatIsNotAResourceIsRejected() : void
    {
        $writer = new DocumentBeingUploadedWriter(new DocumentUploadPathAllocator());

        $this->expectException(\InvalidArgumentException::class);

        $not_a_resource = false;
        $writer->writeChunk(\Mockery::mock(TusFileInformation::class), 0, $not_a_resource);
    }
}
