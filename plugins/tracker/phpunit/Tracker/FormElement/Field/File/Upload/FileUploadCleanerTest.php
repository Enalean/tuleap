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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class FileUploadCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDeleteDanglingFilesToUpload(): void
    {
        $dao                  = \Mockery::mock(FileOngoingUploadDao::class);
        $form_element_factory = \Mockery::mock(Tracker_FormElementFactory::class);
        $logger               = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $field                = \Mockery::mock(\Tracker_FormElement_Field_File::class);

        $logger->shouldReceive('info');

        $base_path = vfsStream::setup()->url();
        mkdir($base_path . '/field/thumbnails', 0777, true);
        \touch($base_path . '/field/11');
        \touch($base_path . '/field/thumbnails/11');
        \touch($base_path . '/field/12');
        \touch($base_path . '/field/13');

        $dao->shouldReceive('deleteUnusableFiles');
        $dao->shouldReceive('searchUnusableFiles')->andReturn(
            [
                [
                    'id'           => 11,
                    'filename'     => 'TaylorSwift.jpg',
                    'description'  => '',
                    'filesize'     => 123,
                    'filetype'     => 'image/jpg',
                    'field_id'     => 1001,
                    'submitted_by' => 101
                ],
                [
                    'id'           => 13,
                    'filename'     => 'Readme.mkd',
                    'description'  => '',
                    'filesize'     => 94830,
                    'filetype'     => 'text/plain',
                    'field_id'     => 1001,
                    'submitted_by' => 101
                ]
            ]
        );

        $form_element_factory->shouldReceive('getFieldById')->andReturn($field);

        $field->shouldReceive('getRootPath')->andReturn($base_path . '/field');

        (new FileUploadCleaner($logger, $dao, $form_element_factory, new DBTransactionExecutorPassthrough()))
            ->deleteDanglingFilesToUpload(new \DateTimeImmutable());

        $this->assertFileNotExists($base_path . '/field/11');
        $this->assertFileNotExists($base_path . '/field/thumbnails/11');
        $this->assertFileExists($base_path . '/field/12');
        $this->assertFileNotExists($base_path . '/field/13');
    }
}
