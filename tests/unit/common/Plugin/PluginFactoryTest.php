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
use Plugin;
use PluginDao;
use PluginFactory;
use PluginResourceRestrictor;
use Tuleap\ForgeConfigSandbox;

final class PluginFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PluginDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PluginResourceRestrictor
     */
    private $restrictor;

    /**
     * @var PluginFactory
     */
    private $factory;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_pluginsroot', __DIR__ . '/_fixtures/plugins');
        ForgeConfig::set('sys_custompluginsroot', __DIR__ . '/_fixtures/customplugins');

        $this->dao = Mockery::mock(PluginDao::class);
        $this->restrictor = Mockery::mock(PluginResourceRestrictor::class);

        $this->factory = new PluginFactory($this->dao, $this->restrictor);

        parent::setUp();
    }

    public function testGetPluginById(): void
    {
        $this->dao->shouldReceive('searchById')->andReturns(\TestHelper::arrayToDar(['name' => 'plugin 123', 'available' => 1]));
        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);
        $plugin = $factory->getPluginById(123);

        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertFalse($factory->getPluginById(124));
    }

    public function testGetPluginByName(): void
    {
        $this->dao->shouldReceive('searchByName')->with('plugin 123')
            ->andReturns(\TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => 1]));
        $this->dao->shouldReceive('searchByName')->andReturns(\TestHelper::emptyDar());

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $plugin_1 = $factory->getPluginByName('plugin 123');
        $this->assertInstanceOf(Plugin::class, $plugin_1);

        $plugin_2 = $factory->getPluginByName('plugin 123');
        $this->assertSame($plugin_1, $plugin_2);

        $this->assertFalse($factory->getPluginByName('plugin 124'));
    }

    public function testCreatePlugin(): void
    {
        $this->dao->shouldReceive('searchByName')->with('existing plugin')
            ->andReturns(\TestHelper::arrayToDar(['id' => 123, 'name' => 'plugin 123', 'available' => '1']));
        $this->dao->shouldReceive('searchByName')->with('new plugin')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('searchByName')->with('error plugin creation')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('create')->with('new plugin', 0)->once()->andReturns(125); //its id
        $this->dao->shouldReceive('create')->with('error plugin creation', 0)->once()->andReturns(false); //error

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $this->assertFalse($factory->createPlugin('existing plugin'));
        $plugin = $factory->createPlugin('new plugin');
        $this->assertEquals(125, $plugin->getId());
        $this->assertFalse($factory->createPlugin('error plugin creation'));
    }

    public function testGetAvailablePlugins(): void
    {
        $this->dao->shouldReceive('searchByAvailable')
            ->andReturns(
                \TestHelper::arrayToDar(
                    ['id' => '123', 'name' => 'plugin 123', 'available' => '1'],
                    ['id' => '124', 'name' => 'plugin 124', 'available' => '1']
                )
            );

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $this->assertCount(2, $factory->getAvailablePlugins());
    }

    public function testGetUnavailablePlugins(): void
    {
        $this->dao->shouldReceive('searchByAvailable')
            ->andReturns(
                \TestHelper::arrayToDar(
                    ['id' => '123', 'name' => 'plugin 123', 'available' => '0'],
                    ['id' => '124', 'name' => 'plugin 124', 'available' => '0']
                )
            );

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $this->assertCount(2, $factory->getUnavailablePlugins());
    }

    public function testGetAllPlugins(): void
    {
        $this->dao->shouldReceive('searchAll')
            ->andReturns(
                \TestHelper::arrayToDar(
                    ['id' => '123', 'name' => 'plugin 123', 'available' => '1'],
                    ['id' => '124', 'name' => 'plugin 124', 'available' => '0']
                )
            );

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $this->assertCount(2, $factory->getAllPlugins());
    }

    public function testIsPluginAvailable(): void
    {
        $this->dao->shouldReceive('searchById')
            ->with(123)
            ->andReturns(\TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => '1']));
        $this->dao->shouldReceive('searchById')
            ->with(124)
            ->andReturns(\TestHelper::arrayToDar(['id' => '124', 'name' => 'plugin 124', 'available' => '0']));

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $plugin_123 = $factory->getPluginById(123);
        $this->assertTrue($factory->isPluginAvailable($plugin_123));

        $plugin_124 = $factory->getPluginById(124);
        $this->assertFalse($factory->isPluginAvailable($plugin_124));
    }

    public function testEnablePlugin(): void
    {
        $this->dao->shouldReceive('searchById')
            ->with(123)
            ->andReturns(\TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => '0']));
        $this->dao->shouldReceive('updateAvailableByPluginId')->with('1', 123)->once();

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $plugin = $factory->getPluginById(123);
        $factory->availablePlugin($plugin);
    }

    public function testDisablePlugin(): void
    {
        $this->dao->shouldReceive('searchById')
            ->with(123)
            ->andReturns(\TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => '1']));
        $this->dao->shouldReceive('updateAvailableByPluginId')->with('0', 123)->once();

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $factory->shouldReceive('_getClassNameForPluginName')->andReturns(['class' => 'Plugin', 'custom' => false]);

        $plugin = $factory->getPluginById(123);
        $factory->unavailablePlugin($plugin);
    }

    public function testPluginIsCustom(): void
    {
        $this->dao->shouldReceive('searchByName')
            ->with('custom')
            ->andReturns(\TestHelper::arrayToDar(['id' => '123', 'name' => 'custom', 'available' => 1]));
        $this->dao->shouldReceive('searchByName')
            ->with('official')
            ->andReturns(\TestHelper::arrayToDar(['id' => '124', 'name' => 'official', 'available' => 1]));

        $factory = \Mockery::mock(\PluginFactory::class . '[_getClassNameForPluginName]', [$this->dao, $this->restrictor]);
        $custom_plugin = new class extends Plugin {
        };
        $custom_plugin_classname = get_class($custom_plugin);
        $factory->shouldReceive('_getClassNameForPluginName')->with('custom')->andReturns(['class' => $custom_plugin_classname, 'custom' => true]);
        $official_plugin = new class extends Plugin {
        };
        $official_plugin_classname = get_class($official_plugin);
        $factory->shouldReceive('_getClassNameForPluginName')->with('official')->andReturns(['class' => $official_plugin_classname, 'custom' => false]);

        $plugin_custom = $factory->getPluginByName('custom');
        $this->assertInstanceOf($custom_plugin_classname, $plugin_custom);
        $this->assertTrue($factory->pluginIsCustom($plugin_custom));

        $plugin_official = $factory->getPluginByName('official');
        $this->assertInstanceOf($official_plugin_classname, $plugin_official);
        $this->assertFalse($factory->pluginIsCustom($plugin_official));
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
