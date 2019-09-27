<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use PluginDao;
use PluginFactory;
use PluginResourceRestrictor;
use Tuleap\ForgeConfigSandbox;

class PluginFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var PluginFactory
     */
    private $factory;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_pluginsroot', __DIR__ . '/_fixtures/plugins');
        ForgeConfig::set('sys_custompluginsroot', __DIR__ . '/_fixtures/customplugins');

        $dao = Mockery::mock(PluginDao::class);
        $restrictor = Mockery::mock(PluginResourceRestrictor::class);

        $this->factory = new PluginFactory($dao, $restrictor);

        parent::setUp();
    }

    public function testInstantiatePluginReturnsNullIfClassIsNotFound(): void
    {
        $this->assertNull($this->factory->instantiatePlugin(1, 'does not exist'));
    }

    /**
     * @testWith ["doc", "docPlugin", false]
     *           ["customdoc", "customdocPlugin", true]
     */
    public function testInstantiatePluginReturnsInstanceWithRegularPHPExtension(
        string $plugin_name,
        string $expected_class,
        bool $expected_is_custom
    ): void {
        $plugin = $this->factory->instantiatePlugin(1, $plugin_name);
        $this->assertInstanceOf($expected_class, $plugin);
        $this->assertEquals($expected_is_custom, $plugin->isCustom());
    }
}
