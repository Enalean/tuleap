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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use Logger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Upload\PathAllocator;

class FileUploadCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDeleteDanglingFilesToUpload(): void
    {
        $dao            = \Mockery::mock(FileOngoingUploadDao::class);
        $path_allocator = \Mockery::mock(PathAllocator::class);
        $logger         = \Mockery::mock(Logger::class);

        $logger->shouldReceive('info');

        $base_path = vfsStream::setup()->url();
        mkdir($base_path . '/file', 0777, true);
        \touch($base_path . '/file/11');
        \touch($base_path . '/file/12');
        \touch($base_path . '/file/13');

        $path_allocator
            ->shouldReceive('getPathForItemBeingUploaded')
            ->withArgs(
                function (TusFileInformation $file_information) {
                    return $file_information->getID() === 11;
                }
            )
            ->andReturn($base_path . '/file/11');

        $path_allocator
            ->shouldReceive('getPathForItemBeingUploaded')
            ->withArgs(
                function (TusFileInformation $file_information) {
                    return $file_information->getID() === 13;
                }
            )
            ->andReturn($base_path . '/file/13');

        $dao->shouldReceive('deleteUnusableFiles');
        $dao->shouldReceive('searchUnusableFiles')->andReturn([
            [
                'id' => 11,
                'filename' => 'Readme.mkd',
                'filesize' => 123
            ],
            [
                'id' => 13,
                'filename' => 'Readme.mkd',
                'filesize' => 94830
            ]
        ]);

        (new FileUploadCleaner($logger, $path_allocator, $dao, new DBTransactionExecutorPassthrough()))
            ->deleteDanglingFilesToUpload(new \DateTimeImmutable());

        $this->assertFileNotExists($base_path . '/file/11');
        $this->assertFileExists($base_path . '/file/12');
        $this->assertFileNotExists($base_path . '/file/13');
    }
}
