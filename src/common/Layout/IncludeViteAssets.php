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
 */

declare(strict_types=1);

namespace Tuleap\Layout;

final class IncludeViteAssets implements IncludeAssetsGeneric
{
    private readonly string $manifest_file;
    private ?array $assets = null;

    public function __construct(string $base_directory, private string $base_url)
    {
        $this->manifest_file = $base_directory . '/.vite/manifest.json';
    }

    public function getFileURL(string $file_name): string
    {
        return $this->getBaseURLWithTrailingSlash() . $this->getHashedName($file_name);
    }

    /**
     * @return string[]
     */
    public function getStylesheetsURLs(string $file_name): array
    {
        $stylesheets = [];

        if ($this->assets === null) {
            $this->assets = $this->loadFromManifest();
        }
        $stylesheet_filename_hashes = $this->assets[$file_name]['css'] ?? [];
        $base_url                   = $this->getBaseURLWithTrailingSlash();

        foreach ($stylesheet_filename_hashes as $stylesheet_filename_hash) {
            $stylesheets[] = $base_url . $stylesheet_filename_hash;
        }

        return $stylesheets;
    }

    private function getBaseURLWithTrailingSlash(): string
    {
        return rtrim($this->base_url, '/') . '/';
    }

    /**
     * @throws IncludeAssetsException
     * @throws IncludeAssetsManifestException
     */
    private function getHashedName(string $file_name): string
    {
        if ($this->assets === null) {
            $this->assets = $this->loadFromManifest();
        }
        if (! isset($this->assets[$file_name]['file'])) {
            throw new IncludeAssetsException(
                "manifest.json doesn't reference $file_name. Did you run `npm run build` ?"
            );
        }
        return $this->assets[$file_name]['file'];
    }

    /**
     * @throws IncludeAssetsManifestException
     */
    private function loadFromManifest(): array
    {
        if (is_file($this->manifest_file)) {
            return json_decode(file_get_contents($this->manifest_file), true, 512, JSON_THROW_ON_ERROR);
        }
        throw new IncludeAssetsManifestException(
            "Asset {$this->manifest_file} doesn't exist. Did you run `npm run build` ?"
        );
    }
}
