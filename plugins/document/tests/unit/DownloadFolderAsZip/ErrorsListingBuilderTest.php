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

use ZipStream\ZipStream;

final class ErrorsListingBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ErrorsListingBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ErrorsListingBuilder();
    }

    public function testItDoesNothingWhenNoErrorsHaveBeenRegistered(): void
    {
        $zip = $this->createMock(ZipStream::class);
        $zip->expects(self::never())->method('addFile');

        $this->builder->addErrorsFileIfAnyToArchive($zip);
    }

    public function testItAddsAnErrorsFileAtTheRootOfTheArchive(): void
    {
        $zip = $this->createMock(ZipStream::class);
        $zip->expects(self::once())
            ->method('addFile')
            ->with('TULEAP_ERRORS.txt', self::isType('string'));
        $this->builder->addBadFilePath('/my folder/my file.jpg');

        $this->builder->addErrorsFileIfAnyToArchive($zip);
    }

    public function testItWritesPathsOfBadFilesInTheErrorsFile(): void
    {
        $zip = $this->createMock(ZipStream::class);
        $zip->expects(self::once())
            ->method('addFile')
            ->will(self::returnCallback(function (string $filename, string $contents): void {
                self::assertStringContainsString('/my folder/my file.jpg', $contents);
                self::assertStringContainsString('embedded file.html', $contents);
            }));

        $this->builder->addBadFilePath('/my folder/my file.jpg');
        $this->builder->addBadFilePath('embedded file.html');

        $this->builder->addErrorsFileIfAnyToArchive($zip);
    }
}
