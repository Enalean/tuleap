<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CssAssetCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetDeduplicatedAssets(): void
    {
        $asset_one   = self::buildCSSAsset('assets/tuleap.css');
        $asset_two   = self::buildCSSAsset('plugins/git/themes/BurningParrot/assets/git.css');
        $asset_three = self::buildCSSAsset('assets/tuleap.css');

        $collection = new CssAssetCollection([$asset_one, $asset_two, $asset_three]);

        $expected = [$asset_one, $asset_two];
        $this->assertEquals($expected, $collection->getDeduplicatedAssets());
    }

    public function testMerge(): void
    {
        $asset_one   = self::buildCSSAsset('assets/tuleap.css');
        $asset_two   = self::buildCSSAsset('plugins/git/themes/BurningParrot/assets/git.css');
        $asset_three = self::buildCSSAsset('assets/tuleap.css');
        $asset_four  = self::buildCSSAsset('assets/widget/widget.css');

        $collection       = new CssAssetCollection([$asset_one, $asset_two]);
        $other_collection = new CssAssetCollection([$asset_three, $asset_four]);

        $merged_collection = $collection->merge($other_collection);

        $expected = [$asset_one, $asset_two, $asset_four];
        $this->assertEquals($expected, $merged_collection->getDeduplicatedAssets());
    }

    private static function buildCSSAsset(string $identifier): CssAssetGeneric
    {
        return new class ($identifier) implements CssAssetGeneric
        {
            public function __construct(private string $identifier)
            {
            }

            public function getFileURL(ThemeVariation $variant): string
            {
                return '/';
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }
}
