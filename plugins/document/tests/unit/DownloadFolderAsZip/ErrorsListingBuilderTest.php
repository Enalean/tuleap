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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ZipStream\ZipStream;

final class ErrorsListingBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ErrorsListingBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new ErrorsListingBuilder();
    }

    public function testItDoesNothingWhenNoErrorsHaveBeenRegistered(): void
    {
        $zip = M::mock(ZipStream::class);
        $zip->shouldNotReceive('addFile');

        $this->builder->addErrorsFileIfAnyToArchive($zip);
    }

    public function testItAddsAnErrorsFileAtTheRootOfTheArchive(): void
    {
        $zip = M::mock(ZipStream::class);
        $zip->shouldReceive('addFile')
            ->once()
            ->with('TULEAP_ERRORS.txt', M::type('string'));
        $this->builder->addBadFilePath('/my folder/my file.jpg');

        $this->builder->addErrorsFileIfAnyToArchive($zip);
    }

    public function testItWritesPathsOfBadFilesInTheErrorsFile(): void
    {
        $zip = M::mock(ZipStream::class);
        $zip->shouldReceive('addFile')
            ->once()
            ->with(M::type('string'), M::capture($file_contents));
        $this->builder->addBadFilePath('/my folder/my file.jpg');
        $this->builder->addBadFilePath('embedded file.html');

        $this->builder->addErrorsFileIfAnyToArchive($zip);

        $this->assertStringContainsString('/my folder/my file.jpg', $file_contents);
        $this->assertStringContainsString('embedded file.html', $file_contents);
    }
}
