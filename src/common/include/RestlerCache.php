<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

/**
 * Manage restler cache
 *
 * Note in Tuleap/REST namespace because of php51
 */
class RestlerCache
{

    public const PREFIX = 'v';

    public const RESTLER_CACHE_FILE = 'routes.php';

    public function getAndInitiateCacheDirectory($version)
    {
        $path = $this->getCacheDirectory() . DIRECTORY_SEPARATOR . self::PREFIX . $version;
        if (! is_dir($path)) {
            mkdir($path, 0700, true);
        }
        return $path;
    }

    public function invalidateCache()
    {
        foreach (glob($this->getCacheDirectory() . DIRECTORY_SEPARATOR . self::PREFIX . '*') as $version_directory) {
            $cache_file = $version_directory . DIRECTORY_SEPARATOR . self::RESTLER_CACHE_FILE;
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
        }
    }

    private function getCacheDirectory()
    {
        return ForgeConfig::get('codendi_cache_dir') . DIRECTORY_SEPARATOR . 'restler';
    }
}
