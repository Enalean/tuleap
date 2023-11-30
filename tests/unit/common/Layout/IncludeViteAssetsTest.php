<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use Tuleap\Test\PHPUnit\TestCase;

final class IncludeViteAssetsTest extends TestCase
{
    private vfsStreamDirectory $assets_dir;

    protected function setUp(): void
    {
        $this->assets_dir = vfsStream::setup();
        $this->assets_dir->addChild(new vfsStreamDirectory('.vite'));
    }

    public function testItReturnsFileURLWithHashedName(): void
    {
        $this->assets_dir->addChild((new vfsStreamFile('.vite/manifest.json'))->setContent('{"file.js": {"file": "file-hashed.js"}}'));
        $include_assets = new IncludeViteAssets($this->assets_dir->url(), '/path/to');

        $this->assertSame('/path/to/file-hashed.js', $include_assets->getFileURL('file.js'));
    }

    public function testItRaisesManifestExceptionIfThereIsNoManifestFile(): void
    {
        $include_assets = new IncludeViteAssets($this->assets_dir->url(), '/path/to');

        $this->expectException(IncludeAssetsManifestException::class);

        $include_assets->getFileURL('some_file.js');
    }

    public function testItRaisesExceptionWhenTheRequestedFileDoesNotExist(): void
    {
        $this->assets_dir->addChild((new vfsStreamFile('.vite/manifest.json'))->setContent('{}'));
        $include_assets = new IncludeViteAssets($this->assets_dir->url(), '/path/to');

        $this->expectException(IncludeAssetsException::class);

        $include_assets->getFileURL('some_file.js');
    }

    public function testItDoesNotDoubleTrailingSlashInFileURL(): void
    {
        $this->assets_dir->addChild((new vfsStreamFile('.vite/manifest.json'))->setContent('{"file.js": {"file": "file-hashed.js"}}'));
        $include_assets = new IncludeViteAssets($this->assets_dir->url(), '/path/to/');

        $this->assertSame('/path/to/file-hashed.js', $include_assets->getFileURL('file.js'));
    }
}
