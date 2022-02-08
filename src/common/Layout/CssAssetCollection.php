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

class CssAssetCollection
{
    /** @var CssAssetGeneric[] */
    private array $css_assets = [];

    /**
     * @param CssAssetGeneric[] $css_assets
     */
    public function __construct(array $css_assets)
    {
        foreach ($css_assets as $asset) {
            $this->addWithoutDuplicate($asset);
        }
    }

    public static function empty(): self
    {
        return new self([]);
    }

    private function addWithoutDuplicate(CssAssetGeneric $asset): void
    {
        if (! isset($this->css_assets[$asset->getIdentifier()])) {
            $this->css_assets[$asset->getIdentifier()] = $asset;
        }
    }

    public function merge(CssAssetCollection $collection): CssAssetCollection
    {
        $all_assets = array_merge($this->css_assets, $collection->getDeduplicatedAssets());
        return new CssAssetCollection($all_assets);
    }

    /**
     * @return CssAssetGeneric[]
     */
    public function getDeduplicatedAssets(): array
    {
        return array_values($this->css_assets);
    }
}
