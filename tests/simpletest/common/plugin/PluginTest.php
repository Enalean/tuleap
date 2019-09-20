<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

Mock::generatePartial('Plugin', 'PluginTestVersion', array('_getPluginManager'));

Mock::generate('PluginManager');

class FakePluginToTestHooks extends Plugin
{

    public function hook1()
    {
    }

    public function hook2()
    {
    }

    public function anEvent()
    {
    }

    public function doSomething()
    {
    }

    public function conflictInCallbacks()
    {
    }

    public function conflict_in_callbacks()
    {
    }
}

class PluginTest extends TuleapTestCase
{

    function testId()
    {
        $p = new Plugin();
        $this->assertEqual($p->getId(), -1);
        $p = new Plugin(123);
        $this->assertEqual($p->getId(), 123);
    }

    function testPluginInfo()
    {
        $p = new Plugin();
        $this->assertIsA($p->getPluginInfo(), 'PluginInfo');
    }

    public function testDefaultCallbackIsHookNameInCamelCase()
    {
        $p = new FakePluginToTestHooks();
        $col = $p->getHooksAndCallbacks();
        $this->assertTrue($col->isEmpty());

        $hook = 'an_event';
        $p->addHook($hook);
        $col = $p->getHooksAndCallbacks();
        $it = $col->iterator();
        $current_hook = $it->current();
        $this->assertEqual($current_hook['hook'], $hook);
        $this->assertEqual($current_hook['callback'], 'anEvent');
        $this->assertFalse($current_hook['recallHook']);
    }

    public function testSpecialCallback()
    {
        $p = new FakePluginToTestHooks();

        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $p->addHook($hook, $callback);
        $col = $p->getHooksAndCallbacks();
        $it = $col->iterator();
        $current_hook = $it->current();
        $this->assertEqual($current_hook['hook'], $hook);
        $this->assertEqual($current_hook['callback'], $callback);
        $this->assertFalse($current_hook['recallHook']);
    }

    public function testAnotherSpecialCallback()
    {
        $p = new FakePluginToTestHooks();

        $hook     = 'name_of_hook';
        $callback = 'doSomething';
        $recall   = true;
        $p->addHook($hook, $callback, $recall);
        $col = $p->getHooksAndCallbacks();
        $it = $col->iterator();
        $current_hook = $it->current();
        $this->assertEqual($current_hook['hook'], $hook);
        $this->assertEqual($current_hook['callback'], $callback);
        $this->assertEqual($current_hook['recallHook'], $recall);
    }

    public function itRaisesAnExceptionWhenThereIsAConflictInAvailableCallbacks()
    {
        $plugin = new FakePluginToTestHooks();

        $this->expectException();
        $plugin->addHook('conflict_in_callbacks');
    }

    public function itRaisesAnExceptionWhenThereIsNoCallbackForTheHook()
    {
        $plugin = new FakePluginToTestHooks();

        $this->expectException();
        $plugin->addHook('no_callback_defined');
    }

    function testScope()
    {
        $p = new Plugin();
        $this->assertIdentical($p->getScope(), Plugin::SCOPE_SYSTEM);
        $this->assertNotEqual($p->getScope(), Plugin::SCOPE_PROJECT);
        $this->assertNotEqual($p->getScope(), Plugin::SCOPE_USER);
    }
    function testGetPluginEtcRoot()
    {
        $GLOBALS['sys_custompluginsroot'] = $this->getTmpDir().'/test/custom/';
        $shortname = 'shortname';
        $pm = new MockPluginManager($this);
        $pm->setReturnValue('getNameForPlugin', $shortname);
        $p = new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);

        $this->assertEqual($p->getPluginEtcRoot(), $GLOBALS['sys_custompluginsroot'].'/'.$shortname.'/etc');
    }
    function testGetPluginPath()
    {
        $GLOBALS['sys_pluginspath']       = '/plugins';
        $GLOBALS['sys_custompluginspath'] = '/customplugins';
        $shortname = 'shortname';
        $pm = new MockPluginManager($this);
        $pm->setReturnValue('pluginIsCustom', true);
        $pm->setReturnValueAt(0, 'pluginIsCustom', false);
        $pm->setReturnValue('getNameForPlugin', $shortname);
        $p = new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);

        $this->assertEqual($p->getPluginPath(), $GLOBALS['sys_pluginspath'].'/'.$shortname);
        $this->assertEqual($p->getPluginPath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname);
    }

    function testGetThemePath()
    {
        $GLOBALS['sys_user_theme']        = 'current_theme';
        $GLOBALS['sys_pluginspath']       = '/plugins';
        $GLOBALS['sys_custompluginspath'] = '/customplugins';
        $GLOBALS['sys_pluginsroot']       = $this->getTmpDir().'/test/plugins/';
        $GLOBALS['sys_custompluginsroot'] = $this->getTmpDir().'/test/custom/';
        mkdir(dirname($GLOBALS['sys_pluginsroot']));

        $shortname     = 'shortname';
        $pm = new MockPluginManager($this);
        $pm->setReturnValue('pluginIsCustom', false);
        $pm->setReturnValueAt(4, 'pluginIsCustom', true);
        $pm->setReturnValueAt(5, 'pluginIsCustom', true);
        $pm->setReturnValue('getNameForPlugin', $shortname);
        $p = new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);

        //Plugin is official
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        $this->assertEqual($p->getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_pluginsroot']);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        $this->assertEqual($p->getThemePath(), $GLOBALS['sys_pluginspath'].'/'.$shortname.'/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname);
        rmdir($GLOBALS['sys_pluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        $this->assertEqual($p->getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_pluginsroot']);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname);
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/default');
        $this->assertEqual($p->getThemePath(), $GLOBALS['sys_pluginspath'].'/'.$shortname.'/themes/default');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/default');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_pluginsroot'].$shortname);
        rmdir($GLOBALS['sys_pluginsroot']);

        //Now plugin is custom
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        $this->assertEqual($p->getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/'.$GLOBALS['sys_user_theme']);
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);
        clearstatcache();
        mkdir($GLOBALS['sys_custompluginsroot']);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname);
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        mkdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        $this->assertEqual($p->getThemePath(), $GLOBALS['sys_custompluginspath'].'/'.$shortname.'/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/default');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/themes/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname.'/www/');
        rmdir($GLOBALS['sys_custompluginsroot'].$shortname);
        rmdir($GLOBALS['sys_custompluginsroot']);

        rmdir(dirname($GLOBALS['sys_custompluginsroot']));
    }

    function testGetThemePathShouldReturnNullIfNoUserTheme()
    {
        unset($GLOBALS['sys_user_theme']);
        $GLOBALS['sys_pluginspath']       = '/plugins';
        $GLOBALS['sys_custompluginspath'] = '/customplugins';
        $GLOBALS['sys_pluginsroot']       = $this->getTmpDir().'/test/plugins/';
        $GLOBALS['sys_custompluginsroot'] = $this->getTmpDir().'/test/custom/';

        $shortname     = 'shortname';
        $pm = new MockPluginManager($this);
        $pm->setReturnValue('getNameForPlugin', $shortname);

        $p = new PluginTestVersion($this);
        $p->setReturnReference('_getPluginManager', $pm);

        $this->assertEqual($p->getThemePath(), '');
    }

    function testGetFilesystemPath()
    {
        $GLOBALS['sys_pluginsroot']       = '/my/application';

        $pm = new MockPluginManager($this);
        $pm->setReturnValue('getNameForPlugin', 'zataz');
        $pm->setReturnValue('pluginIsCustom', false);

        $p = new PluginTestVersion($this);
        $p->setReturnValue('_getPluginManager', $pm);

        $this->assertEqual($p->getFilesystemPath(), '/my/application/zataz');
    }

    function testGetFilesystemPathCustom()
    {
        $GLOBALS['sys_custompluginsroot']       = '/my/custom/application';

        $pm = new MockPluginManager($this);
        $pm->setReturnValue('getNameForPlugin', 'zataz');
        $pm->setReturnValue('pluginIsCustom', true);

        $p = new PluginTestVersion($this);
        $p->setReturnValue('_getPluginManager', $pm);

        $this->assertEqual($p->getFilesystemPath(), '/my/custom/application/zataz');
    }

    function testGetFilesystemPathWithSlashAtTheEnd()
    {
        $GLOBALS['sys_pluginsroot']       = '/my/application/';

        $pm = new MockPluginManager($this);
        $pm->setReturnValue('getNameForPlugin', 'zataz');
        $pm->setReturnValue('pluginIsCustom', false);

        $p = new PluginTestVersion($this);
        $p->setReturnValue('_getPluginManager', $pm);

        $this->assertEqual($p->getFilesystemPath(), '/my/application/zataz');
    }

    function itHasNoDependenciesByDefault()
    {
        $plugin = new Plugin();
        $this->assertArrayEmpty($plugin->getDependencies());
    }

    function itDoesntAllowToListenSameHookSeveralTimes()
    {
        $this->expectException();
        $plugin = new Plugin();
        $plugin->addHook('bla');
        $plugin->addHook('bla');
    }
}
