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

namespace Tuleap\APIExplorer;

use PHPUnit\Framework\TestCase;
use Tuleap\REST\ExplorerEndpointAvailableEvent;

final class api_explorerPluginTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testBuildsPluginInformation(): void
    {
        $plugin      = new \api_explorerPlugin(12);
        $plugin_info = $plugin->getPluginInfo();

        $this->assertNotEmpty($plugin_info->getPluginDescriptor()->getFullName());
        $this->assertNotEmpty($plugin_info->getPluginDescriptor()->getDescription());
    }

    public function testMarksTheAPIExplorerAsAvailable(): void
    {
        $event = new ExplorerEndpointAvailableEvent();

        $plugin = new \api_explorerPlugin(13);
        $plugin->explorerEndpointAvailableEvent($event);

        $this->assertNotNull($event->getEndpointURL());
        $this->assertTrue(isset($plugin->getHooksAndCallbacks()->toArray()[ExplorerEndpointAvailableEvent::NAME]));
    }
}
