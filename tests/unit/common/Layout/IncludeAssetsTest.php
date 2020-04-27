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

namespace Tuleap\Layout;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class IncludeAssetsTest extends TestCase
{
    /**
     * @var string
     */
    private $assets_dir_path;

    protected function setUp(): void
    {
        $this->assets_dir_path = vfsStream::setup()->url() . '/assets';
        mkdir($this->assets_dir_path);
    }

    public function testItRaisesManifestExceptionIfThereIsNoManifestFile(): void
    {
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->expectException(IncludeAssetsManifestException::class);

        $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js');
    }

    public function testItReturnsFileURLWithHashedName(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"file.js":"file-hashed.js"}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->assertEquals('/path/to/file-hashed.js', $include_assets->getFileURL('file.js'));
    }

    public function testItDoesNotDoubleTrailingSlashInFileURL(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"file.js":"file-hashed.js"}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to/');

        $this->assertEquals('/path/to/file-hashed.js', $include_assets->getFileURL('file.js'));
    }

    public function testItReturnsFileURLWithNonHashedName(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"file.js":"file-hashed.js"}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->assertEquals('/path/to/file.js', $include_assets->getPath('file.js'));
    }

    public function testItReturnsJavascriptHTMLSnippet(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"file.js":"file-hashed.js"}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->assertEquals(
            '<script type="text/javascript" src="/path/to/file-hashed.js"></script>' . PHP_EOL,
            $include_assets->getHTMLSnippet('file.js')
        );
    }

    public function testItRaisesAssetsExceptionIfBothFileAndFallbackDoNotExist(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->expectException(IncludeAssetsException::class);

        $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js');
    }

    public function testItReturnFallbackIfFileDoesNotExist(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"fallback.js":"fallback-hashed.js"}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->assertEquals(
            '/path/to/fallback-hashed.js',
            $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js')
        );
    }

    public function testItDoesNotReturnFallbackIfFileExists(): void
    {
        file_put_contents($this->assets_dir_path . '/manifest.json', '{"aFile.js":"aFile-hashed.js"}');
        $include_assets = new IncludeAssets($this->assets_dir_path, '/path/to');

        $this->assertEquals(
            '/path/to/aFile-hashed.js',
            $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js')
        );
    }
}
