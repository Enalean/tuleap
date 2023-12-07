<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\REST;

use Event;
use Luracast\Restler\Defaults;
use Luracast\Restler\Format\JsonFormat;
use org\bovigo\vfs\vfsStream;

final class RestlerFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsRestler(): void
    {
        $restler_cache          = $this->createMock(\RestlerCache::class);
        $core_resource_injector = $this->createMock(ResourcesInjector::class);
        $event_manager          = $this->createMock(\EventManager::class);

        $restler_factory = new RestlerFactory($restler_cache, $core_resource_injector, $event_manager);

        $expected_cache_directory = vfsStream::setup()->url();
        $restler_cache->method('getAndInitiateCacheDirectory')->willReturn($expected_cache_directory);
        $core_resource_injector->expects(self::once())->method('populate');
        $event_manager->expects(self::once())->method('processEvent')->with(Event::REST_RESOURCES, self::anything());

        $restler = $restler_factory->buildRestler(147);

        self::assertEquals(147, $restler->getApiVersion());

        self::assertTrue(Defaults::$useUrlBasedVersioning);
        self::assertFalse(JsonFormat::$unEscapedUnicode);
        self::assertEquals($expected_cache_directory, Defaults::$cacheDirectory);
    }
}
