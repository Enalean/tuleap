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
use EventManager;
use Luracast\Restler\Defaults;
use Luracast\Restler\Format\JsonFormat;
use Luracast\Restler\Restler;
use RestlerCache;

final class RestlerFactory
{
    /**
     * @var RestlerCache
     */
    private $restler_cache;
    /**
     * @var ResourcesInjector
     */
    private $core_resources_injector;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(RestlerCache $restler_cache, ResourcesInjector $core_resources_injector, EventManager $event_manager)
    {
        $this->restler_cache           = $restler_cache;
        $this->core_resources_injector = $core_resources_injector;
        $this->event_manager           = $event_manager;
    }

    public function buildRestler(int $api_version): Restler
    {
        // Use /api/v1/projects uri
        Defaults::$useUrlBasedVersioning = true;

        // Do not unescape unicode or it will break the api (see request #9162)
        JsonFormat::$unEscapedUnicode = false;

        Defaults::$cacheDirectory = $this->restler_cache->getAndInitiateCacheDirectory($api_version);
        $restler = new Restler(true, false);
        $restler->setSupportedFormats('JsonFormat', 'XmlFormat');
        $restler->setAPIVersion($api_version);

        $this->core_resources_injector->populate($restler);
        $this->event_manager->processEvent(Event::REST_RESOURCES, ['restler' => $restler]);

        return $restler;
    }
}
