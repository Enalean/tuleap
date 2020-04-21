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
use Tuleap\Upload\PathAllocator;

class EmptyFileToUploadFinisherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCreateEmptyFile(): void
    {
        $file_to_upload = new FileToUpload(42, 'readme.md');

        $path           = vfsStream::setup()->url() . '/file/42';
        $path_allocator = \Mockery::mock(PathAllocator::class);
        $path_allocator->shouldReceive('getPathForItemBeingUploaded')->andReturn($path);

        (new EmptyFileToUploadFinisher($path_allocator))->createEmptyFile(
            $file_to_upload,
            'readme.md'
        );
        $this->assertEquals('', file_get_contents($path));
    }
}
