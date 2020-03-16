<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Layout;

class IncludeAssets
{


    public const MANIFEST_FILE_NAME = 'manifest.json';

    private $base_url;
    private $assets;
    private $manifest_file;

    public function __construct($base_directory, $base_url)
    {
        $this->base_url      = $base_url;
        $this->manifest_file = $base_directory . '/' . self::MANIFEST_FILE_NAME;
    }

    public function getHTMLSnippet($file_name)
    {
        return '<script type="text/javascript" src="' . $this->getFileURL($file_name) . '"></script>' . PHP_EOL;
    }

    public function getFileURL($file_name)
    {
        return $this->base_url . '/' . $this->getHashedName($file_name);
    }

    /**
     * @throws IncludeAssetsException
     * @throws IncludeAssetsManifestException
     */
    public function getFileURLWithFallback(string $file_name, string $fallback_filename): string
    {
        try {
            return $this->base_url . '/' . $this->getHashedName($file_name);
        } catch (IncludeAssetsException $exception) {
            return $this->base_url . '/' . $this->getHashedName($fallback_filename);
        }
    }

    public function getPath($file_name)
    {
        return $this->base_url . '/' . $file_name;
    }

    private function getHashedName($file_name)
    {
        if ($this->assets === null) {
            $this->loadFromManifest();
        }
        if (! isset($this->assets[$file_name])) {
            throw new IncludeAssetsException("manifest.json doesn't reference $file_name. Did you run `npm run build` ?");
        }
        return $this->assets[$file_name];
    }

    private function loadFromManifest()
    {
        if (is_file($this->manifest_file)) {
            $this->assets = json_decode(file_get_contents($this->manifest_file), true);
        } else {
            throw new IncludeAssetsManifestException("Asset {$this->manifest_file} doesn't exist. Did you run `npm run build` ?");
        }
    }
}
