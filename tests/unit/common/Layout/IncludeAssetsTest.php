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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class IncludeAssetsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItRaisesManifestExceptionIfThereIsNoManifestFile(): void
    {
        $assets_dir = vfsStream::setup()->url() . '/assets';
        mkdir($assets_dir);
        $include_assets = new IncludeAssets($assets_dir, '/path/to');

        $this->expectException(IncludeAssetsManifestException::class);

        $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js');
    }

    public function testItRaisesAssetsExceptionIfBothFileAndFallbackDoNotExist(): void
    {
        $assets_dir = vfsStream::setup()->url() . '/assets';
        mkdir($assets_dir);
        file_put_contents($assets_dir . '/manifest.json', '{}');
        $include_assets = new IncludeAssets($assets_dir, '/path/to');

        $this->expectException(IncludeAssetsException::class);

        $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js');
    }

    public function testItReturnFallbackIfFileDoNotExist(): void
    {
        $assets_dir = vfsStream::setup()->url() . '/assets';
        mkdir($assets_dir);
        file_put_contents($assets_dir . '/manifest.json', '{"fallback.js":"fallback-hashed.js"}');
        $include_assets = new IncludeAssets($assets_dir, '/path/to');

        $this->assertEquals(
            '/path/to/fallback-hashed.js',
            $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js')
        );
    }

    public function testItDoesNotReturnFallbackIfFileExisst(): void
    {
        $assets_dir = vfsStream::setup()->url() . '/assets';
        mkdir($assets_dir);
        file_put_contents($assets_dir . '/manifest.json', '{"aFile.js":"aFile-hashed.js"}');
        $include_assets = new IncludeAssets($assets_dir, '/path/to');

        $this->assertEquals(
            '/path/to/aFile-hashed.js',
            $include_assets->getFileURLWithFallback('aFile.js', 'fallback.js')
        );
    }
}
