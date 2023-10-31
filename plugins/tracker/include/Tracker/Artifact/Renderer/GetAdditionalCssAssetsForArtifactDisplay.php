<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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


namespace Tuleap\Tracker\Artifact\Renderer;

use Tuleap\Event\Dispatchable;
use Tuleap\Layout\CssAssetGeneric;

final class GetAdditionalCssAssetsForArtifactDisplay implements Dispatchable
{
    public const NAME = 'getAdditionalCssAssetsForArtifactDisplay';

    /**
     * @var list<CssAssetGeneric>
     */
    private array $css_assets = [];

    public function __construct(private readonly string $view_identifier)
    {
    }

    /**
     * @return list<CssAssetGeneric>
     */
    public function getCssAssets(): array
    {
        return $this->css_assets;
    }

    public function addCssAsset(CssAssetGeneric $asset): void
    {
        $this->css_assets[] = $asset;
    }

    public function getViewIdentifier(): string
    {
        return $this->view_identifier;
    }
}
