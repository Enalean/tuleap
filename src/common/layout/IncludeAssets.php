<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

class IncludeAssets
{
    public const MANIFEST_FILE_NAME = 'manifest.json';

    private $base_url;
    private $assets;
    private $manifest_file;

    public function __construct(string $base_directory, string $base_url)
    {
        $this->base_url      = $base_url;
        $this->manifest_file = $base_directory . '/' . self::MANIFEST_FILE_NAME;
    }

    /**
     * @throws IncludeAssetsException
     * @throws IncludeAssetsManifestException
     */
    public function getHTMLSnippet(string $file_name): string
    {
        return '<script type="text/javascript" src="' . $this->getFileURL($file_name) . '"></script>' . PHP_EOL;
    }

    /**
     * @throws IncludeAssetsException
     * @throws IncludeAssetsManifestException
     */
    public function getFileURL(string $file_name): string
    {
        return $this->getBaseURLWithTrailingSlash() . $this->getHashedName($file_name);
    }

    /**
     * @throws IncludeAssetsException
     * @throws IncludeAssetsManifestException
     */
    public function getFileURLWithFallback(string $file_name, string $fallback_filename): string
    {
        try {
            return $this->getFileURL($file_name);
        } catch (IncludeAssetsException $exception) {
            return $this->getFileURL($fallback_filename);
        }
    }

    public function getPath(string $file_name): string
    {
        return $this->getBaseURLWithTrailingSlash() . $file_name;
    }

    /**
     * @throws IncludeAssetsException
     * @throws IncludeAssetsManifestException
     */
    private function getHashedName(string $file_name): string
    {
        if ($this->assets === null) {
            $this->loadFromManifest();
        }
        if (! isset($this->assets[$file_name])) {
            throw new IncludeAssetsException(
                "manifest.json doesn't reference $file_name. Did you run `npm run build` ?"
            );
        }
        return $this->assets[$file_name];
    }

    /**
     * @throws IncludeAssetsManifestException
     */
    private function loadFromManifest(): void
    {
        if (is_file($this->manifest_file)) {
            $this->assets = json_decode(file_get_contents($this->manifest_file), true);
        } else {
            throw new IncludeAssetsManifestException(
                "Asset {$this->manifest_file} doesn't exist. Did you run `npm run build` ?"
            );
        }
    }

    private function getBaseURLWithTrailingSlash(): string
    {
        return rtrim($this->base_url, '/') . '/';
    }
}
