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

namespace Tuleap\Tracker\FormElement\Field\Files\Upload\Tus;

use ForgeConfig;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElementFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Files\FilesField;
use Tuleap\Tracker\FormElement\Field\Files\Upload\FileOngoingUploadDao;
use Tuleap\Upload\FileBeingUploadedInformation;

#[DisableReturnValueGenerationForTestDoubles]
final class FileUploadFinisherTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testThumbnailIsGenerated(): void
    {
        ForgeConfig::set('sys_http_user', 'codendiadm');
        $dao     = $this->createMock(FileOngoingUploadDao::class);
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        $field   = $this->createMock(FilesField::class);

        $base_path = vfsStream::setup()->url() . '/field';
        \mkdir($base_path . '/thumbnails', 0777, true);
        \copy(__DIR__ . '/_fixtures/Lenna.png', $base_path . '/42');

        $dao->method('searchFileOngoingUploadById')->willReturn([
            'field_id'     => 1001,
            'id'           => 42,
            'submitted_by' => 101,
            'description'  => '',
            'filename'     => 'Lenna.png',
            'filesize'     => 473831,
            'filetype'     => 'image/png',
        ]);

        $field->method('getRootPath')->willReturn($base_path);

        $factory->method('getFieldById')->willReturn($field);

        $file_information = new FileBeingUploadedInformation(42, 'Lenna.png', 473831, 473831);

        (new FileUploadFinisher($dao, $factory))->finishUpload(new NullServerRequest(), $file_information);
        self::assertFileEquals(__DIR__ . '/_fixtures/Lenna-expected-thumbnail.png', $base_path . '/thumbnails/42');
    }
}
