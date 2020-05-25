<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Document\DownloadFolderAsZip;

use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Folders\ComputeFolderSizeVisitor;
use Tuleap\Document\Config\FileDownloadLimits;

final class FolderSizeIsAllowedCheckerTest extends TestCase
{
    /**
     * @var FolderSizeIsAllowedChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = new FolderSizeIsAllowedChecker(new ComputeFolderSizeVisitor());
    }

    public function testItReturnsFalseWhenFolderSizeIsAboveLimit(): void
    {
        $limits = new FileDownloadLimits(1, 50);
        $folder = $this->createFolderWithFileSize(2000000);

        $result = $this->checker->checkFolderSizeIsBelowLimit($folder, $limits);
        $this->assertFalse($result);
    }

    public function testItReturnsTrueWhenFolderSizeIsBelowOrEqualsLimit(): void
    {
        $limits = new FileDownloadLimits(1, 50);
        $folder = $this->createFolderWithFileSize(1000000);

        $result = $this->checker->checkFolderSizeIsBelowLimit($folder, $limits);
        $this->assertTrue($result);
    }

    private function createFolderWithFileSize(int $file_size): \Docman_Folder
    {
        $item    = new \Docman_File();
        $version = new \Docman_Version();
        $version->setFilesize($file_size);
        $item->setCurrentVersion($version);
        $folder = new \Docman_Folder();
        $folder->addItem($item);
        return $folder;
    }
}
