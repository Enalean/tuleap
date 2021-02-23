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
 *
 */

namespace Tuleap\Layout;

class CssAsset
{
    /** @var IncludeAssets */
    protected $include_assets;
    /** @var string */
    protected $name;

    public function __construct(IncludeAssets $include_assets, $name)
    {
        $this->include_assets = $include_assets;
        $this->name           = $name;
    }

    public function getFileURL(ThemeVariation $variant)
    {
        return $this->include_assets->getFileURL($this->name . $variant->getFileColorCondensedSuffix() . '.css');
    }

    public function getPath()
    {
        return $this->include_assets->getPath($this->name);
    }
}
