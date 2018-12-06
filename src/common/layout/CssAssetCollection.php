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

class CssAssetCollection
{
    /** @var CssAsset[] */
    private $css_assets = [];

    /**
     * @param CssAsset[] $css_assets
     */
    public function __construct(array $css_assets)
    {
        foreach ($css_assets as $asset) {
            $this->addWithoutDuplicate($asset);
        }
    }

    private function addWithoutDuplicate(CssAsset $asset)
    {
        if (! isset($this->css_assets[$asset->getPath()])) {
            $this->css_assets[$asset->getPath()] = $asset;
        }
    }

    /**
     * @return CssAssetCollection
     */
    public function merge(CssAssetCollection $collection)
    {
        $all_assets = array_merge($this->css_assets, $collection->getDeduplicatedAssets());
        return new CssAssetCollection($all_assets);
    }

    /**
     * @return CssAsset[]
     */
    public function getDeduplicatedAssets()
    {
        return array_values($this->css_assets);
    }
}
