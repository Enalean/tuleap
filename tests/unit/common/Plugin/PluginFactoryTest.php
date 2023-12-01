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
use PHPUnit\Framework\MockObject\MockObject;
use Plugin;
use PluginDao;
use PluginFactory;
use PluginResourceRestrictor;
use Tuleap\ForgeConfigSandbox;

final class PluginFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private PluginDao&MockObject $dao;
    private PluginResourceRestrictor&MockObject $restrictor;

    private PluginFactory $factory;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_pluginsroot', __DIR__ . '/_fixtures/plugins');
        ForgeConfig::set('sys_custompluginsroot', __DIR__ . '/_fixtures/customplugins');

        $this->dao        = $this->createMock(PluginDao::class);
        $this->restrictor = $this->createMock(PluginResourceRestrictor::class);

        $this->factory = new PluginFactory($this->dao, $this->restrictor);

        parent::setUp();
    }

    public function testGetPluginById(): void
    {
        $this->dao->method('searchById')->willReturn(\TestHelper::arrayToDar(['name' => 'plugin 123', 'available' => 1]));
        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);
        $plugin = $factory->getPluginById(123);

        self::assertInstanceOf(Plugin::class, $plugin);
        self::assertNull($factory->getPluginById(124));
    }

    public function testGetPluginByName(): void
    {
        $this->dao->method('searchByName')
            ->withConsecutive(
                ['plugin 123'],
                ['plugin 124']
            )
            ->willReturnOnConsecutiveCalls(
                \TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => 1]),
                \TestHelper::emptyDar()
            );

        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);

        $plugin_1 = $factory->getPluginByName('plugin 123');
        self::assertInstanceOf(Plugin::class, $plugin_1);

        $plugin_2 = $factory->getPluginByName('plugin 123');
        self::assertSame($plugin_1, $plugin_2);

        self::assertNull($factory->getPluginByName('plugin 124'));
    }

    public function testCreatePlugin(): void
    {
        $this->dao->method('searchByName')
            ->withConsecutive(
                ['existing plugin'],
                ['new plugin'],
                ['error plugin creation']
            )
            ->willReturnOnConsecutiveCalls(
                \TestHelper::arrayToDar(['id' => 123, 'name' => 'plugin 123', 'available' => '1']),
                \TestHelper::emptyDar(),
                \TestHelper::emptyDar()
            );
        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                ['new plugin'],
                ['error plugin creation']
            )
            ->willReturnOnConsecutiveCalls(
                125, //its id
                false //error
            );

        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);

        self::assertNull($factory->createPlugin('existing plugin'));
        $plugin = $factory->createPlugin('new plugin');
        self::assertEquals(125, $plugin->getId());
        self::assertNull($factory->createPlugin('error plugin creation'));
    }

    public function testGetEnablePlugins(): void
    {
        $this->dao->method('searchEnabledPlugins')
            ->willReturn(
                \TestHelper::arrayToDar(
                    ['id' => '123', 'name' => 'plugin 123', 'available' => '1'],
                    ['id' => '124', 'name' => 'plugin 124', 'available' => '1']
                )
            );

        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);

        self::assertCount(2, $factory->getEnabledPlugins());
    }

    public function testGetAllPlugins(): void
    {
        $this->dao->method('searchAll')
            ->willReturn(
                \TestHelper::arrayToDar(
                    ['id' => '123', 'name' => 'plugin 123', 'available' => '1'],
                    ['id' => '124', 'name' => 'plugin 124', 'available' => '0']
                )
            );

        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);

        self::assertCount(2, $factory->getAllPlugins());
    }

    public function testIsPluginAvailable(): void
    {
        $this->dao->method('searchById')
            ->withConsecutive(
                [123],
                [124]
            )
            ->willReturnOnConsecutiveCalls(
                \TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => '1']),
                \TestHelper::arrayToDar(['id' => '124', 'name' => 'plugin 124', 'available' => '0'])
            );

        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);

        $plugin_123 = $factory->getPluginById(123);
        self::assertTrue($factory->isPluginEnabled($plugin_123));

        $plugin_124 = $factory->getPluginById(124);
        self::assertFalse($factory->isPluginEnabled($plugin_124));
    }

    public function testEnablePlugin(): void
    {
        $this->dao->expects(self::once())->method('enablePlugin')->with(123);

        $plugin = new class (123) extends Plugin {
            public bool $post_enable_called = false;

            public function postEnable(): void
            {
                $this->post_enable_called = true;
            }
        };

        $this->factory->enablePlugin($plugin);

        self::assertTrue($plugin->post_enable_called);
    }

    public function testCannotEnablePluginWithAMissingInstallRequirement(): void
    {
        $plugin = new class extends Plugin {
            public function getName(): string
            {
                return 'test_plugin';
            }

            public function getInstallRequirements(): array
            {
                return [
                    new class implements PluginInstallRequirement {
                        public function getDescriptionOfMissingInstallRequirement(): ?string
                        {
                            return null;
                        }
                    },
                    new class implements PluginInstallRequirement {
                        public function getDescriptionOfMissingInstallRequirement(): ?string
                        {
                            return 'missing_requirement';
                        }
                    },
                ];
            }
        };

        self::expectException(MissingInstallRequirementException::class);
        self::expectExceptionMessageMatches('/test_plugin.+missing_requirement/');
        $this->factory->enablePlugin($plugin);
    }

    public function testDisablePlugin(): void
    {
        $this->dao->method('searchById')
            ->with(123)
            ->willReturn(\TestHelper::arrayToDar(['id' => '123', 'name' => 'plugin 123', 'available' => '1']));
        $this->dao->expects(self::once())->method('disablePlugin')->with(123);

        $factory = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $factory->method('getClassNameForPluginName')->willReturn(['class' => 'Plugin', 'custom' => false]);

        $plugin = $factory->getPluginById(123);
        $factory->disablePlugin($plugin);
    }

    public function testPluginIsCustom(): void
    {
        $this->dao->method('searchByName')
            ->withConsecutive(
                ['custom'],
                ['official']
            )
            ->willReturnOnConsecutiveCalls(
                \TestHelper::arrayToDar(['id' => '123', 'name' => 'custom', 'available' => 1]),
                \TestHelper::arrayToDar(['id' => '124', 'name' => 'official', 'available' => 1])
            );

        $factory                   = $this->getMockBuilder(\PluginFactory::class)
            ->setConstructorArgs([$this->dao, $this->restrictor])
            ->onlyMethods(['getClassNameForPluginName'])
            ->getMock();
        $custom_plugin             = new class extends Plugin {
        };
        $custom_plugin_classname   = $custom_plugin::class;
        $official_plugin           = new class extends Plugin {
        };
        $official_plugin_classname = $official_plugin::class;
        $factory->method('getClassNameForPluginName')
            ->withConsecutive(
                ['custom'],
                ['official']
            )->willReturnOnConsecutiveCalls(
                ['class' => $custom_plugin_classname, 'custom' => true],
                ['class' => $official_plugin_classname, 'custom' => false]
            );

        $plugin_custom = $factory->getPluginByName('custom');
        self::assertInstanceOf($custom_plugin_classname, $plugin_custom);
        self::assertTrue($factory->isACustomPlugin($plugin_custom));

        $plugin_official = $factory->getPluginByName('official');
        self::assertInstanceOf($official_plugin_classname, $plugin_official);
        self::assertFalse($factory->isACustomPlugin($plugin_official));
    }

    public function testInstantiatePluginReturnsNullIfClassIsNotFound(): void
    {
        self::assertNull($this->factory->instantiatePlugin(1, 'does not exist'));
    }

    /**
     * @testWith ["doc", "docPlugin", false]
     *           ["customdoc", "customdocPlugin", true]
     */
    public function testInstantiatePluginReturnsInstanceWithRegularPHPExtension(
        string $plugin_name,
        string $expected_class,
        bool $expected_is_custom,
    ): void {
        $plugin = $this->factory->instantiatePlugin(1, $plugin_name);
        self::assertInstanceOf($expected_class, $plugin);
        self::assertEquals($expected_is_custom, $plugin->isCustom());
    }
}
