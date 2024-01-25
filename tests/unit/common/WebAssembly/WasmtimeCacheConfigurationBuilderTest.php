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

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

final class WasmtimeCacheConfigurationBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testCreatesConfigFile(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup()->url());

        $config_builder = new WasmtimeCacheConfigurationBuilder();
        $path           = $config_builder->buildCacheConfiguration()->unwrapOr('/file_does_not_exist');

        self::assertFileExists($path);
        self::assertStringContainsString('enabled = true', file_get_contents($path));
    }

    public function testCleanupsConfig(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup()->url());

        $config_builder = new WasmtimeCacheConfigurationBuilder();
        $path           = $config_builder->buildCacheConfiguration()->unwrapOr('/file_does_not_exist');

        self::assertFileExists($path);

        WasmtimeCacheConfigurationBuilder::invalidateCache();

        self::assertFileDoesNotExist($path);
    }
}
