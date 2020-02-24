<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CssAssetCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetDeduplicatedAssets()
    {
        $asset_one = Mockery::mock(CssAsset::class);
        $asset_one->shouldReceive('getPath')->andReturns('assets/tuleap.css');
        $asset_two = Mockery::mock(CssAsset::class);
        $asset_two->shouldReceive('getPath')->andReturns('plugins/git/themes/BurningParrot/assets/git.css');
        $asset_three = Mockery::mock(CssAsset::class);
        $asset_three->shouldReceive('getPath')->andReturns('assets/tuleap.css');

        $collection = new CssAssetCollection([$asset_one, $asset_two, $asset_three]);

        $expected = [$asset_one, $asset_two];
        $this->assertEquals($expected, $collection->getDeduplicatedAssets());
    }

    public function testMerge()
    {
        $asset_one = Mockery::mock(CssAsset::class);
        $asset_one->shouldReceive('getPath')->andReturns('assets/tuleap.css');
        $asset_two = Mockery::mock(CssAsset::class);
        $asset_two->shouldReceive('getPath')->andReturns('plugins/git/themes/BurningParrot/assets/git.css');
        $asset_three = Mockery::mock(CssAsset::class);
        $asset_three->shouldReceive('getPath')->andReturns('assets/tuleap.css');
        $asset_four = Mockery::mock(CssAsset::class);
        $asset_four->shouldReceive('getPath')->andReturns('assets/widget/widget.css');

        $collection       = new CssAssetCollection([$asset_one, $asset_two]);
        $other_collection = new CssAssetCollection([$asset_three, $asset_four]);

        $merged_collection = $collection->merge($other_collection);

        $expected = [$asset_one, $asset_two, $asset_four];
        $this->assertEquals($expected, $merged_collection->getDeduplicatedAssets());
    }
}
