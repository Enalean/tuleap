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

namespace Tuleap\Layout;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class JavascriptAssetTest extends TestCase
{
    public function testItReturnsHashedFileURL(): void
    {
        $assets_dir_path = vfsStream::setup()->url() . '/assets';
        mkdir($assets_dir_path);
        file_put_contents($assets_dir_path . '/manifest.json', '{"file.js":"file-hashed.js"}');
        $script_asset = new JavascriptAsset(new IncludeAssets($assets_dir_path, '/path/to'), 'file.js');

        $this->assertEquals('/path/to/file-hashed.js', $script_asset->getFileURL());
    }
}
