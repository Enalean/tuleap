<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Mapper;

use CuyZ\Valinor\Cache\FileSystemCache;
use CuyZ\Valinor\MapperBuilder;
use Tuleap\Option\Option;

final class ValinorMapperBuilderFactory
{
    private function __construct()
    {
    }

    public static function mapperBuilder(): MapperBuilder
    {
        $mapper_builder = new MapperBuilder();
        return self::buildCache()->mapOr(
            fn (FileSystemCache $cache): MapperBuilder => $mapper_builder->withCache($cache),
            $mapper_builder,
        );
    }

    /**
     * @psalm-return Option<FileSystemCache>
     */
    private static function buildCache(): Option
    {
        if (posix_getpwuid(posix_geteuid())['name'] === \ForgeConfig::getApplicationUserLogin()) {
            return Option::fromValue(new FileSystemCache(self::getCachePath()));
        }

        return Option::nothing(FileSystemCache::class);
    }

    public static function invalidateCache(): void
    {
        $cache_path = self::getCachePath();
        if (\Psl\Filesystem\exists($cache_path)) {
            \Psl\Filesystem\delete_directory(self::getCachePath(), true);
        }
    }

    /**
     * @psalm-return non-empty-string
     */
    private static function getCachePath(): string
    {
        $cache_path = \ForgeConfig::getCacheDir() . '/valinor_cache/';
        assert($cache_path !== '');
        return $cache_path;
    }
}
