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
 *
 */

namespace Tuleap\Layout;

use ThemeVariantColor;

class CssAsset
{
    /**
     * @var IncludeAssets
     */
    private $include_assets;
    private $name;

    public function __construct(IncludeAssets $include_assets, $name)
    {
        $this->include_assets = $include_assets;
        $this->name = $name;
    }

    public function getFileURL(ThemeVariantColor $color)
    {
        return $this->include_assets->getFileURL($this->name.'-'.$color->getName().'.css');
    }
}
