<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\WebAssembly;

use Tuleap\Option\Option;

final class WasmtimeCacheConfigurationBuilder implements WASMCacheConfigurationBuilder
{
    /**
     * @psalm-return Option<non-empty-string>
     */
    public function buildCacheConfiguration(): Option
    {
        $cache_path = self::getCachePath();

        \Psl\Filesystem\create_directory($cache_path);

        $config_file_path = self::getCacheConfigPath();
        if (! \Psl\Filesystem\exists($config_file_path)) {
            \Psl\File\write($config_file_path, $this->buildWasmtimeCacheConfigurationFile($cache_path));
        }

        return Option::fromValue($config_file_path);
    }

    /**
     * @psalm-pure
     */
    private function buildWasmtimeCacheConfigurationFile(string $cache_path): string
    {
        return <<<EOF
        [cache]
        enabled = true
        directory = "$cache_path"
        cleanup-interval = "12h"
        EOF;
    }

    public static function invalidateCache(): void
    {
        $cache_path = self::getCachePath();
        if (\Psl\Filesystem\exists($cache_path)) {
            \Psl\Filesystem\delete_directory($cache_path, true);
        }
        $config_file = self::getCacheConfigPath();
        if (\Psl\Filesystem\exists($config_file)) {
            \Psl\Filesystem\delete_file($config_file);
        }
    }

    /**
     * @psalm-return non-empty-string
     */
    private static function getCachePath(): string
    {
        $cache_path = \ForgeConfig::getCacheDir() . '/wasmtime_cache/';
        assert($cache_path !== '');
        return $cache_path;
    }

    /**
     * @psalm-return non-empty-string
     */
    private static function getCacheConfigPath(): string
    {
        $cache_config_path = \ForgeConfig::getCacheDir() . '/wasmtime-cache-config.toml';
        assert($cache_config_path !== '');
        return $cache_config_path;
    }
}
