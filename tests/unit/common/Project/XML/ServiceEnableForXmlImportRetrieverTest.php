<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\XML;

use PHPUnit\Framework\MockObject\MockObject;
use PluginFactory;

final class ServiceEnableForXmlImportRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ServiceEnableForXmlImportRetriever $retriever;
    private PluginFactory&MockObject $plugin_factory;

    protected function setUp(): void
    {
        $this->plugin_factory = $this->createMock(PluginFactory::class);
        $this->plugin_factory->method('getEnabledPlugins');
        $this->retriever = new ServiceEnableForXmlImportRetriever($this->plugin_factory);
    }

    public function testServiceIsDisabledIfPluginIsRestricted(): void
    {
        $plugin = $this->createMock(\Plugin::class);
        $this->plugin_factory->method('isProjectPluginRestricted')->willReturn(true);

        $this->retriever->addServiceIfPluginIsNotRestricted($plugin, 'plugin_name');

        self::assertEquals([], $this->retriever->getAvailableServices());
    }

    public function testItStoreNotRestrictedServices(): void
    {
        $plugin = $this->createMock(\Plugin::class);
        $this->plugin_factory->method('isProjectPluginRestricted')->willReturn(false);

        $this->retriever->addServiceIfPluginIsNotRestricted($plugin, 'plugin_name');

        self::assertEquals(['plugin_name' => true], $this->retriever->getAvailableServices());
    }
}
