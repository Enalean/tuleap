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

namespace Tuleap\FRS\Upload;

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FRS\Upload\Tus\FileUploadFinisher;
use Tuleap\Tus\TusFileInformation;

final class EmptyFileToUploadFinisherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testCreateEmptyFile(): void
    {
        \ForgeConfig::set('tmp_dir', vfsStream::setup()->url());

        $file_to_upload = new FileToUpload(42);

        $upload_path_allocator = new UploadPathAllocator();

        $finisher = $this->createMock(FileUploadFinisher::class);
        $finisher->expects(self::once())
            ->method('finishUploadFile')
            ->with(self::callback(
                static function (TusFileInformation $file_information) {
                    return $file_information->getID() === 42;
                }
            ));

        (new EmptyFileToUploadFinisher($finisher, $upload_path_allocator))->createEmptyFile(
            $file_to_upload,
            'readme.md'
        );
    }
}
