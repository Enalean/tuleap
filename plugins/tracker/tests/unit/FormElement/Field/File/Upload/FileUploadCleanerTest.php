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

use ColinODell\PsrTestLogger\TestLogger;
use DateTimeImmutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class FileUploadCleanerTest extends TestCase
{
    public function testDeleteDanglingFilesToUpload(): void
    {
        $dao                  = $this->createMock(FileOngoingUploadDao::class);
        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $logger               = new TestLogger();
        $field                = $this->createMock(Tracker_FormElement_Field_File::class);

        $base_path = vfsStream::setup()->url();
        mkdir($base_path . '/field/thumbnails', 0777, true);
        touch($base_path . '/field/11');
        touch($base_path . '/field/thumbnails/11');
        touch($base_path . '/field/12');
        touch($base_path . '/field/13');

        $dao->method('deleteUnusableFiles');
        $dao->method('searchUnusableFiles')->willReturn([
            [
                'id'           => 11,
                'filename'     => 'TaylorSwift.jpg',
                'description'  => '',
                'filesize'     => 123,
                'filetype'     => 'image/jpg',
                'field_id'     => 1001,
                'submitted_by' => 101,
            ],
            [
                'id'           => 13,
                'filename'     => 'Readme.mkd',
                'description'  => '',
                'filesize'     => 94830,
                'filetype'     => 'text/plain',
                'field_id'     => 1001,
                'submitted_by' => 101,
            ],
        ]);

        $form_element_factory->method('getFieldById')->willReturn($field);

        $field->method('getRootPath')->willReturn($base_path . '/field');

        (new FileUploadCleaner($logger, $dao, $form_element_factory, new DBTransactionExecutorPassthrough()))
            ->deleteDanglingFilesToUpload(new DateTimeImmutable());

        self::assertFileDoesNotExist($base_path . '/field/11');
        self::assertFileDoesNotExist($base_path . '/field/thumbnails/11');
        self::assertFileExists($base_path . '/field/12');
        self::assertFileDoesNotExist($base_path . '/field/13');
        self::assertTrue($logger->hasInfoRecords());
    }
}
