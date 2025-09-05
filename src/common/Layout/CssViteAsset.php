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

final class CssViteAsset implements CssAssetGeneric
{
    private function __construct(private string $file_url)
    {
    }

    public static function buildCollectionFromMainFileName(IncludeViteAssets $include_assets, string $file_name): CssAssetCollection
    {
        return new CssAssetCollection(
            array_map(
                static fn (string $file_url) => new self($file_url),
                $include_assets->getStylesheetsURLs($file_name)
            )
        );
    }

    /**
     * @throws IncludeAssetsManifestException
     * @throws IncludeAssetsException
     */
    public static function fromFileName(IncludeViteAssets $include_assets, string $file_name): self
    {
        return new self($include_assets->getFileURL($file_name));
    }

    #[\Override]
    public function getFileURL(ThemeVariation $variant): string
    {
        return $this->file_url;
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return basename($this->file_url);
    }
}
