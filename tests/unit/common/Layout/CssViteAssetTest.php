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
 *
 */

declare(strict_types=1);

namespace Tuleap\Layout;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Tuleap\Test\PHPUnit\TestCase;

final class CssViteAssetTest extends TestCase
{
    public function testBuildsCssAssets(): void
    {
        $assets_dir = vfsStream::setup();
        $assets_dir->addChild(new vfsStreamDirectory('.vite'));
        file_put_contents(
            $assets_dir->url() . '/.vite/manifest.json',
            <<<EOF
            {
              "main.ts": {
                "file": "assets/main.b9ed4664.js",
                "src": "main.ts",
                "isEntry": true,
                "css": [
                  "assets/a.aaaaaaaa.css",
                  "assets/b.ffffffff.css"
                ]
              }
            }
            EOF
        );
        $include_assets = new IncludeViteAssets($assets_dir->url(), '/');

        $collection = CssViteAsset::buildCollectionFromMainFileName($include_assets, 'main.ts');
        $css_assets = $collection->getDeduplicatedAssets();

        $theme_variation = $this->createStub(ThemeVariation::class);
        self::assertEquals(
            ['/assets/a.aaaaaaaa.css', '/assets/b.ffffffff.css'],
            array_map(static fn (CssAssetGeneric $css_asset) => $css_asset->getFileURL($theme_variation), $css_assets)
        );
        self::assertEquals(
            ['a.aaaaaaaa.css', 'b.ffffffff.css'],
            array_map(static fn (CssAssetGeneric $css_asset) => $css_asset->getIdentifier(), $css_assets)
        );
    }

    public function testBuildsNoCSSAssetsWhenNothingIsAssociatedToTheMainFile(): void
    {
        $assets_dir = vfsStream::setup();
        $assets_dir->addChild(new vfsStreamDirectory('.vite'));
        file_put_contents(
            $assets_dir->url() . '/.vite/manifest.json',
            <<<EOF
            {
              "main.ts": {
                "file": "assets/main.b9ed4664.js",
                "src": "main.ts",
                "isEntry": true
              }
            }
            EOF
        );
        $include_assets = new IncludeViteAssets($assets_dir->url(), '/');

        $collection = CssViteAsset::buildCollectionFromMainFileName($include_assets, 'main.ts');

        self::assertEquals(CssAssetCollection::empty(), $collection);
    }

    public function testItBuildsFromACSSOnlyAssetInManifest(): void
    {
        $assets_dir = vfsStream::setup();
        $assets_dir->addChild(new vfsStreamDirectory('.vite'));
        file_put_contents(
            $assets_dir->url() . '/.vite/manifest.json',
            <<<EOF
            {
              "style.scss": {
                "file": "assets/style.155a00bb.css",
                "src": "style.scss",
                "isEntry": true
              }
            }
            EOF
        );
        $include_assets = new IncludeViteAssets($assets_dir->url(), '/');

        $css_asset = CssViteAsset::fromFileName($include_assets, 'style.scss');

        $theme_variation = $this->createStub(ThemeVariation::class);
        self::assertSame('/assets/style.155a00bb.css', $css_asset->getFileURL($theme_variation));
        self::assertSame('style.155a00bb.css', $css_asset->getIdentifier());
    }
}
