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

namespace Tuleap\Tracker\FormElement\Field\File\Upload\Tus;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Upload\FileBeingUploadedInformation;

class FileUploadFinisherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testThumbnailIsGenerated(): void
    {
        ForgeConfig::set('sys_http_user', 'codendiadm');
        $dao     = \Mockery::mock(FileOngoingUploadDao::class);
        $factory = \Mockery::mock(Tracker_FormElementFactory::class);
        $field   = \Mockery::mock(Tracker_FormElement_Field_File::class);

        $base_path = vfsStream::setup()->url() . '/field';
        \mkdir($base_path . '/thumbnails', 0777, true);
        \copy(__DIR__ . '/_fixtures/Lenna.png', $base_path . '/42');

        $dao->shouldReceive('searchFileOngoingUploadById')->andReturn(
            [
                'field_id'     => 1001,
                'id'           => 42,
                'submitted_by' => 101,
                'description'  => '',
                'filename'     => 'Lenna.png',
                'filesize'     => 473831,
                'filetype'     => 'image/png',
            ]
        );

        $field->shouldReceive('getRootPath')->andReturn($base_path);

        $factory->shouldReceive('getFieldById')->andReturn($field);

        $file_information = new FileBeingUploadedInformation(42, 'Lenna.png', 473831, 473831);

        (new FileUploadFinisher($dao, $factory))->finishUpload(new NullServerRequest(), $file_information);
        $this->assertFileEquals(__DIR__ . '/_fixtures/Lenna-expected-thumbnail.png', $base_path . '/thumbnails/42');
    }
}
