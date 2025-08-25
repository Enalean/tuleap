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

namespace Tuleap\Tracker\FormElement\Field\Files;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FileInfo;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class AttachmentToFinalPlaceMoverTest extends TestCase
{
    public function testItMovesTheFile(): void
    {
        $fileinfo = $this->createMock(Tracker_FileInfo::class);
        $fileinfo->method('getPath')->willReturn('/path/to/dest');
        $fileinfo->expects($this->once())->method('postUploadActions');
        $fileinfo->expects($this->never())->method('delete');

        $method = function (string $path_a, string $path_b): bool {
            if ($path_a !== '/path/to/file' || $path_b !== '/path/to/dest') {
                self::fail();
            }
            return true;
        };

        $mover = new AttachmentToFinalPlaceMover();
        $mover->moveAttachmentToFinalPlace($fileinfo, $method, '/path/to/file');
    }

    public function testItFailsMovingTheFile(): void
    {
        $fileinfo = $this->createMock(Tracker_FileInfo::class);
        $fileinfo->method('getPath')->willReturn('/path/to/dest');
        $fileinfo->expects($this->never())->method('postUploadActions');
        $fileinfo->expects($this->once())->method('delete');

        $method = function (string $path_a, string $path_b): bool {
            if ($path_a !== '/path/to/file' || $path_b !== '/path/to/dest') {
                self::fail();
            }
            return false;
        };

        $mover = new AttachmentToFinalPlaceMover();
        $mover->moveAttachmentToFinalPlace($fileinfo, $method, '/path/to/file');
    }
}
