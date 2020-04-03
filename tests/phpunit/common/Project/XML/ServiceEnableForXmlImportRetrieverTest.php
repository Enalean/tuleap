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

use Mockery;
use PHPUnit\Framework\TestCase;
use PluginFactory;

final class ServiceEnableForXmlImportRetrieverTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ServiceEnableForXmlImportRetriever
     */
    private $retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PluginFactory
     */
    private $plugin_factory;

    protected function setUp(): void
    {
        $this->plugin_factory = Mockery::mock(PluginFactory::class);
        $this->plugin_factory->shouldReceive('getAvailablePlugins');
        $this->retriever = new ServiceEnableForXmlImportRetriever($this->plugin_factory);
    }

    public function testServiceIsDisabledIfPluginIsRestricted(): void
    {
        $plugin = Mockery::mock(\Plugin::class);
        $this->plugin_factory->shouldReceive('isProjectPluginRestricted')->andReturnTrue();

        $this->retriever->addServiceIfPluginIsNotRestricted($plugin, 'plugin_name');

        $this->assertEquals([], $this->retriever->getAvailableServices());
    }

    public function testItStoreNotRestrictedServices(): void
    {
        $plugin = Mockery::mock(\Plugin::class);
        $this->plugin_factory->shouldReceive('isProjectPluginRestricted')->andReturnFalse();

        $this->retriever->addServiceIfPluginIsNotRestricted($plugin, 'plugin_name');

        $this->assertEquals(['plugin_name' => true], $this->retriever->getAvailableServices());
    }
}
