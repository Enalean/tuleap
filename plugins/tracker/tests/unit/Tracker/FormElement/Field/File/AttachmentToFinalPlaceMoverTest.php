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

namespace Tuleap\Tracker\FormElement\Field\File;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AttachmentToFinalPlaceMoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItMovesTheFile(): void
    {
        $fileinfo = \Mockery::mock(\Tracker_FileInfo::class);
        $fileinfo->shouldReceive('getPath')->andReturn('/path/to/dest');
        $fileinfo->shouldReceive('postUploadActions')->once();
        $fileinfo->shouldReceive('delete')->never();

        $method = function (string $path_a, string $path_b): bool {
            if ($path_a !== '/path/to/file' || $path_b !== '/path/to/dest') {
                $this->fail();
            }
            return true;
        };

        $mover = new AttachmentToFinalPlaceMover();
        $mover->moveAttachmentToFinalPlace($fileinfo, $method, '/path/to/file');
    }

    public function testItFailsMovingTheFile(): void
    {
        $fileinfo = \Mockery::mock(\Tracker_FileInfo::class);
        $fileinfo->shouldReceive('getPath')->andReturn('/path/to/dest');
        $fileinfo->shouldReceive('postUploadActions')->never();
        $fileinfo->shouldReceive('delete')->once();

        $method = function (string $path_a, string $path_b): bool {
            if ($path_a !== '/path/to/file' || $path_b !== '/path/to/dest') {
                $this->fail();
            }
            return false;
        };

        $mover = new AttachmentToFinalPlaceMover();
        $mover->moveAttachmentToFinalPlace($fileinfo, $method, '/path/to/file');
    }
}
