<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Event;
use Exception;
use ForgeConfig;
use LogicException;
use Plugin;
use PluginInfo;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Layout\HomePage\LastMonthStatisticsCollectorSVN;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class PluginTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    public function testId(): void
    {
        $p = new Plugin();
        self::assertEquals(-1, $p->getId());
        $p = new Plugin(123);
        self::assertEquals(123, $p->getId());
    }

    public function testPluginInfo(): void
    {
        $p = new Plugin();
        self::assertInstanceOf(PluginInfo::class, $p->getPluginInfo());
    }

    public function testDefaultCallbackIsHookNameInCamelCase(): void
    {
        $p   = $this->getFakePluginToTestHooks();
        $col = $p->getHooksAndCallbacks();
        self::assertTrue($col->isEmpty());

        $hook = 'an_event';
        $p->addHook($hook);
        $col          = $p->getHooksAndCallbacks();
        $it           = $col->iterator();
        $current_hook = $it->current();
        self::assertEquals($hook, $current_hook['hook']);
        self::assertEquals('anEvent', $current_hook['callback']);
    }

    public function testSpecialCallback(): void
    {
        $p = $this->getFakePluginToTestHooks();

        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $p->addHook($hook, $callback);
        $col          = $p->getHooksAndCallbacks();
        $it           = $col->iterator();
        $current_hook = $it->current();
        self::assertEquals($hook, $current_hook['hook']);
        self::assertEquals($callback, $current_hook['callback']);
    }

    public function testAnotherSpecialCallback(): void
    {
        $p = $this->getFakePluginToTestHooks();

        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $p->addHook($hook, $callback);
        $col          = $p->getHooksAndCallbacks();
        $it           = $col->iterator();
        $current_hook = $it->current();
        self::assertEquals($hook, $current_hook['hook']);
        self::assertEquals($callback, $current_hook['callback']);
    }

    public function testRaisesAnExceptionWhenThereIsAConflictInAvailableCallbacks(): void
    {
        $plugin = $this->getFakePluginToTestHooks();

        self::expectException(Exception::class);
        $plugin->addHook('conflict_in_callbacks');
    }

    public function testRaisesAnExceptionWhenThereIsNoCallbackForTheHook(): void
    {
        $plugin = $this->getFakePluginToTestHooks();

        self::expectException(Exception::class);
        $plugin->addHook('no_callback_defined');
    }

    public function testScope(): void
    {
        $p = new Plugin();
        self::assertEquals(Plugin::SCOPE_SYSTEM, $p->getScope());
        self::assertNotEquals(Plugin::SCOPE_PROJECT, $p->getScope());
    }

    public function testGetPluginEtcRoot(): void
    {
        $root = \org\bovigo\vfs\vfsStream::setup()->url();

        ForgeConfig::set('sys_custompluginsroot', $root . '/test/custom/');
        $shortname = 'shortname';
        $pm        = $this->createMock(\PluginManager::class);
        $pm->method('getNameForPlugin')->willReturn($shortname);
        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        self::assertEquals(ForgeConfig::get('sys_custompluginsroot') . '/' . $shortname . '/etc', $p->getPluginEtcRoot());
    }

    public function testGetPluginPath(): void
    {
        ForgeConfig::set('sys_pluginspath', '/plugins');
        $shortname = 'shortname';
        $pm        = $this->createMock(\PluginManager::class);

        $pm->expects(self::once())->method('isACustomPlugin')->willReturn(false);
        $pm->method('getNameForPlugin')->willReturn($shortname);
        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        self::assertEquals(ForgeConfig::get('sys_pluginspath') . '/' . $shortname, $p->getPluginPath());
    }

    public function testGetThemePath(): void
    {
        $tmp_dir = \org\bovigo\vfs\vfsStream::setup()->url();
        ForgeConfig::set('sys_user_theme', 'current_theme');
        ForgeConfig::set('sys_pluginspath', '/plugins');
        ForgeConfig::set('sys_custompluginspath', '/customplugins');
        ForgeConfig::set('sys_pluginsroot', $tmp_dir . '/test/plugins/');
        ForgeConfig::set('sys_custompluginsroot', $tmp_dir . '/test/custom/');
        mkdir(dirname(ForgeConfig::get('sys_pluginsroot')));

        $shortname = 'shortname';
        $pm        = $this->createMock(\PluginManager::class);
        $pm->method('isACustomPlugin')->willReturn(false, true, true);
        $pm->method('getNameForPlugin')->willReturn($shortname);
        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        //Plugin is official
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        self::assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/' . ForgeConfig::get('sys_user_theme'), $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_pluginsroot'));
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        self::assertEquals(ForgeConfig::get('sys_pluginspath') . '/' . $shortname . '/themes/' . ForgeConfig::get('sys_user_theme'), $p->getThemePath());
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_pluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        self::assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/default', $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_pluginsroot'));
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/default');
        self::assertEquals(ForgeConfig::get('sys_pluginspath') . '/' . $shortname . '/themes/default', $p->getThemePath());
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/default');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_pluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_pluginsroot'));

        //Now plugin is custom
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        self::assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/' . ForgeConfig::get('sys_user_theme'), $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/' . ForgeConfig::get('sys_user_theme'));
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));
        clearstatcache();
        mkdir(ForgeConfig::get('sys_custompluginsroot'));
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        mkdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        self::assertEquals(ForgeConfig::get('sys_custompluginspath') . '/' . $shortname . '/themes/default', $p->getThemePath());
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/default');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/themes/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname . '/www/');
        rmdir(ForgeConfig::get('sys_custompluginsroot') . $shortname);
        rmdir(ForgeConfig::get('sys_custompluginsroot'));

        rmdir(dirname(ForgeConfig::get('sys_custompluginsroot')));
    }

    public function testGetThemePathShouldReturnNullIfNoUserTheme(): void
    {
        $tmp_dir = \org\bovigo\vfs\vfsStream::setup()->url();
        ForgeConfig::set('sys_pluginspath', '/plugins');
        ForgeConfig::set('sys_custompluginspath', '/customplugins');
        ForgeConfig::set('sys_pluginsroot', $tmp_dir . '/test/plugins/');
        ForgeConfig::set('sys_custompluginsroot', $tmp_dir . '/test/custom/');

        $shortname = 'shortname';
        $pm        = $this->createMock(\PluginManager::class);
        $pm->method('getNameForPlugin')->willReturn($shortname);

        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        self::assertEquals('', $p->getThemePath());
    }

    public function testGetFilesystemPath(): void
    {
        ForgeConfig::set('sys_pluginsroot', '/my/application');

        $pm = $this->createMock(\PluginManager::class);
        $pm->method('getNameForPlugin')->willReturn('zataz');
        $pm->method('isACustomPlugin')->willReturn(false);

        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        self::assertEquals('/my/application/zataz', $p->getFilesystemPath());
    }

    public function testGetFilesystemPathCustom(): void
    {
        ForgeConfig::set('sys_custompluginsroot', '/my/custom/application');

        $pm = $this->createMock(\PluginManager::class);
        $pm->method('getNameForPlugin')->willReturn('zataz');
        $pm->method('isACustomPlugin')->willReturn(true);

        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        self::assertEquals('/my/custom/application/zataz', $p->getFilesystemPath());
    }

    public function testGetFilesystemPathWithSlashAtTheEnd(): void
    {
        ForgeConfig::set('sys_pluginsroot', '/my/application');

        $pm = $this->createMock(\PluginManager::class);
        $pm->method('getNameForPlugin')->willReturn('zataz');
        $pm->method('isACustomPlugin')->willReturn(false);

        $p = $this->createPartialMock(\Plugin::class, [
            '_getPluginManager',
        ]);
        $p->method('_getPluginManager')->willReturn($pm);

        self::assertEquals('/my/application/zataz', $p->getFilesystemPath());
    }

    public function testHasNoDependenciesByDefault(): void
    {
        $plugin = new Plugin();
        self::assertEmpty($plugin->getDependencies());
    }

    public function testNotAllowedToListenSameHookSeveralTimes(): void
    {
        $plugin = new class extends \Plugin {
            public function bla(): void
            {
            }
        };
        self::expectException(Exception::class);
        $plugin->addHook('bla');
        $plugin->addHook('bla');
    }

    public function testSettingIsRestrictedByPassDatabaseCall(): void
    {
        $plugin = $this->createPartialMock(\Plugin::class, [
            'getServiceShortname',
            'isAllowed',
        ]);
        $plugin->method('getServiceShortname')->willReturn('fooservice');
        $plugin->setIsRestricted(false);
        $plugin->expects(self::never())->method('isAllowed');
        $services = [];
        $params   = [
            'project' => ProjectTestBuilder::aProject()->build(),
            'services' => &$services,
        ];
        $plugin->servicesAllowedForProject($params);

        self::assertEquals(['fooservice'], $services);
    }

    public function testNoSettingIsRestrictedFallsbackOnDbCheck(): void
    {
        $plugin = $this->createPartialMock(Plugin::class, [
            'getServiceShortname',
            'isAllowed',
        ]);
        $plugin->method('getServiceShortname')->willReturn('fooservice');
        $plugin->expects(self::once())->method('isAllowed')->willReturn(true);
        $services = [];
        $params   = [
            'project' => ProjectTestBuilder::aProject()->withId(101)->build(),
            'services' => &$services,
        ];
        $plugin->servicesAllowedForProject($params);

        self::assertEquals(['fooservice'], $services);
    }

    public function testSettingIsRestrictedToTrueFallsbackOnDbCheck(): void
    {
        $plugin = $this->createPartialMock(Plugin::class, [
            'getServiceShortname',
            'isAllowed',
        ]);
        $plugin->method('getServiceShortname')->willReturn('fooservice');
        $plugin->setIsRestricted(true);
        $plugin->expects(self::once())->method('isAllowed')->willReturn(true);
        $services = [];
        $params   = [
            'project' => ProjectTestBuilder::aProject()->withId(101)->build(),
            'services' => &$services,
        ];
        $plugin->servicesAllowedForProject($params);

        self::assertEquals(['fooservice'], $services);
    }

    public function testPluginIsNotAllowedToProject(): void
    {
        $plugin = $this->createPartialMock(Plugin::class, [
            'getServiceShortname',
            'isAllowed',
        ]);
        $plugin->method('getServiceShortname')->willReturn('fooservice');
        $plugin->expects(self::once())->method('isAllowed')->willReturn(false);
        $services = [];
        $params   = [
            'project' => ProjectTestBuilder::aProject()->withId(101)->build(),
            'services' => &$services,
        ];
        $plugin->servicesAllowedForProject($params);

        self::assertEquals([], $services);
    }

    public function testImplementingPluginWithConfigKeysIsEnoughToListenToGetConfigKeysEvent(): void
    {
        $plugin = new class extends \Plugin implements PluginWithConfigKeys {
            public function getConfigKeys(\Tuleap\Config\ConfigClassProvider $event): void
            {
            }
        };

        self::assertArrayHasKey('getConfigKeys', $plugin->getHooksAndCallbacks()->toArray());
    }

    public function testImplementingPluginWithConfigKeysAndListeningToEventManuallyDoesntRaiseException(): void
    {
        $plugin = new class extends \Plugin implements PluginWithConfigKeys {
            public function getHooksAndCallbacks()
            {
                $this->addHook(\Tuleap\Config\GetConfigKeys::NAME);
                return parent::getHooksAndCallbacks();
            }

            public function getConfigKeys(\Tuleap\Config\ConfigClassProvider $event): void
            {
            }
        };

        self::assertArrayHasKey('getConfigKeys', $plugin->getHooksAndCallbacks()->toArray());
    }

    public function testImplementingPluginWithServiceIsEnoughToListenToEvents(): void
    {
        $plugin = new class extends \Plugin implements PluginWithService {
            public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
            {
            }

            public function serviceIsUsed(array $params): void
            {
            }

            public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event,): void
            {
            }

            public function serviceDisabledCollector(ServiceDisabledCollector $event): void
            {
            }

            public function addMissingService(AddMissingService $event): void
            {
            }

            public function serviceEnableForXmlImportRetriever(\Tuleap\Project\XML\ServiceEnableForXmlImportRetriever $event): void
            {
            }
        };

        self::assertArrayHasKey(Event::SERVICES_ALLOWED_FOR_PROJECT, $plugin->getHooksAndCallbacks()->toArray());
        self::assertArrayHasKey(Event::SERVICE_IS_USED, $plugin->getHooksAndCallbacks()->toArray());
        self::assertArrayHasKey(ProjectServiceBeforeActivation::NAME, $plugin->getHooksAndCallbacks()->toArray());
        self::assertArrayHasKey(ServiceDisabledCollector::NAME, $plugin->getHooksAndCallbacks()->toArray());
        self::assertArrayHasKey(AddMissingService::NAME, $plugin->getHooksAndCallbacks()->toArray());
    }

    public function testPluginUsesListeningToEventClassAttribute(): void
    {
        $plugin = new class extends \Plugin {
            #[\Tuleap\Plugin\ListeningToEventClass]
            public function someCallbackMethod(SiteAdministrationAddOption $option): void
            {
            }
        };

        $hooks_and_callbacks = $plugin->getHooksAndCallbacks()->toArray();
        self::assertArrayHasKey(SiteAdministrationAddOption::NAME, $hooks_and_callbacks);
        self::assertEquals('someCallbackMethod', $hooks_and_callbacks[SiteAdministrationAddOption::NAME]['callback']);
    }

    public function testPluginUsesListeningToEventClassWithoutNameAttribute(): void
    {
        $plugin = new class extends \Plugin {
            #[\Tuleap\Plugin\ListeningToEventClass]
            public function someCallbackMethod(LastMonthStatisticsCollectorSVN $option): void
            {
            }
        };

        $hooks_and_callbacks = $plugin->getHooksAndCallbacks()->toArray();
        self::assertArrayHasKey(LastMonthStatisticsCollectorSVN::class, $hooks_and_callbacks);
        self::assertEquals('someCallbackMethod', $hooks_and_callbacks[LastMonthStatisticsCollectorSVN::class]['callback']);
    }

    public function testPluginCannotListeningHooksTwice(): void
    {
        $plugin = new class extends \Plugin {
            public function getHooksAndCallbacks()
            {
                $this->addHook(SiteAdministrationAddOption::NAME);
                return parent::getHooksAndCallbacks();
            }

            #[\Tuleap\Plugin\ListeningToEventClass]
            public function siteAdministrationAddOption(SiteAdministrationAddOption $option): void
            {
            }
        };

        self::expectException(LogicException::class);

        $plugin->getHooksAndCallbacks();
    }

    public function testPluginIsListeningToEventThatIsNotAvailableBecausePluginIsNotInstalled(): void
    {
        $plugin = new class extends \Plugin {
            #[\Tuleap\Plugin\ListeningToEventClass]
            public function someCallbackMethod(SomeEventFromNotLoadedPlugin $option): void
            {
            }
        };

        self::assertEmpty($plugin->getHooksAndCallbacks()->toArray());
    }

    public function testPluginUsesListeningToEventNameAttribute(): void
    {
        $plugin = new class extends \Plugin {
            #[\Tuleap\Plugin\ListeningToEventName('foo_bar')]
            public function someCallbackMethod(): void
            {
            }
        };

        $hooks_and_callbacks = $plugin->getHooksAndCallbacks()->toArray();
        self::assertArrayHasKey('foo_bar', $hooks_and_callbacks);
        self::assertEquals('someCallbackMethod', $hooks_and_callbacks['foo_bar']['callback']);
    }

    private function getFakePluginToTestHooks(): Plugin
    {
        return new class extends Plugin {
            public function hook1(): void
            {
            }

            public function hook2(): void
            {
            }

            public function anEvent(): void
            {
            }

            public function doSomething(): void
            {
            }

            public function conflictInCallbacks(): void
            {
            }

            public function conflict_in_callbacks(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
            }
        };
    }
}
