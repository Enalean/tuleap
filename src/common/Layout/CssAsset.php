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

class CssAsset implements CssAssetGeneric
{
    /**
     * @var IncludeAssets
     * @psalm-readonly
     */
    protected $include_assets;
    /**
     * @var string
     * @psalm-readonly
     */
    protected $name;

    public function __construct(IncludeAssets $include_assets, $name)
    {
        $this->include_assets = $include_assets;
        $this->name           = $name;
    }

    #[\Override]
    public function getFileURL(ThemeVariation $variant): string
    {
        return $this->include_assets->getFileURL($this->name . $variant->getFileColorSuffix() . '.css');
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->include_assets->getPath($this->name);
    }
}
