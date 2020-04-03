<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Plugin;

use FastRoute\RouteCollector;
use Plugin;
use Tuleap\Request\CollectRoutesEvent;

abstract class PluginWithLegacyInternalRouting extends Plugin
{
    abstract public function process(): void;

    final protected function listenToCollectRouteEventWithDefaultController(): void
    {
        $this->addHook(CollectRoutesEvent::NAME, 'defaultCollectRoutesEvent');
    }

    final public function defaultCollectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routePluginLegacyController'));
        });
    }

    final public function routePluginLegacyController(): PluginLegacyController
    {
        return new PluginLegacyController($this);
    }
}
