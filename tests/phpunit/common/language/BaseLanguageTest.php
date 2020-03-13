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
 *
 */

class BaseLanguageTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\TemporaryTestDirectory;

    protected $cache_dir;

    protected function setUp() : void
    {
        parent::setUp();
        ForgeConfig::store();

        $this->cache_dir = $this->getTmpDir();

        ForgeConfig::set('sys_pluginsroot', __DIR__ . '/_fixtures/tuleap/plugins');
        ForgeConfig::set('sys_extra_plugin_path', '');
        $GLOBALS['sys_incdir']            = __DIR__ . '/_fixtures/tuleap/site-content';
        $GLOBALS['sys_pluginsroot']       = ForgeConfig::get('sys_pluginsroot');
        $GLOBALS['sys_custom_incdir']     = __DIR__ . '/_fixtures/etc/site-content';
        $GLOBALS['sys_custompluginsroot'] = __DIR__ . '/_fixtures/etc/plugins';

        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);
        if (! is_dir($this->cache_dir . '/lang')) {
            mkdir($this->cache_dir . '/lang', 0777, true);
        }
    }

    protected function tearDown() : void
    {
        unset($GLOBALS['sys_incdir']);
        unset($GLOBALS['sys_pluginsroot']);
        unset($GLOBALS['sys_custom_incdir']);
        unset($GLOBALS['sys_custompluginsroot']);

        UserManager::clearInstance();
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testConstructorIsOkWhenLanguagesAreKnown()
    {
        $l1 = new BaseLanguage(',lang1,lang2, lang3 ,lang4 , lang5,', 'lang1');
        $this->assertEquals(['lang1', 'lang2', 'lang3', 'lang4', 'lang5'], $l1->allLanguages);
    }

    public function testConstructorThrowExceptionWhenLanguageNotSupported()
    {
        $this->expectExceptionMessage('The default language must be part of supported languages');
        new BaseLanguage('lang1,lang2', 'do-not-exist');
    }

    public function testConstructorThrowExceptionWhenNoLanguageProvided()
    {
        $this->expectExceptionMessage('You must provide supported languages (see local.inc)');
        new BaseLanguage('', '');
    }

    public function testParseAcceptLanguage()
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        $this->assertEquals(
            [
                'en-ca' => 1,
                'en'    => 0.8,
                'en-us' => 0.6,
                'de-de' => 0.4,
                'de'    => 0.2,
            ],
            $l->parseAcceptLanguage('en-ca,en;q=0.8,en-us;q=0.6,de-de;q=0.4,de;q=0.2')
        );

        $this->assertEquals(
            [
                'en-us' => 1,
                'en'    => 0.8,
                'fr'    => 0.5,
                'fr-fr' => 0.3,
            ],
            $l->parseAcceptLanguage('en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3')
        );

        $this->assertEquals([], $l->parseAcceptLanguage(''));
    }

    public function testGetLanguageFromAcceptLanguage()
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        $this->assertEquals('en_US', $l->getLanguageFromAcceptLanguage(''));
        $this->assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en'));
        $this->assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en-us'));
        $this->assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en-ca'));
        $this->assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3'));
        $this->assertEquals('en_US', $l->getLanguageFromAcceptLanguage('de-de'));
        $this->assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr'));
        $this->assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr-fr'));
        $this->assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr-ca'));
        $this->assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr-fr,fr;q=0.8,en-us;q=0.5,en;q=0.3'));

        $l2 = new BaseLanguage('en_US,fr_FR', 'fr_FR');
        $this->assertEquals('fr_FR', $l2->getLanguageFromAcceptLanguage(''));
        $this->assertEquals('fr_FR', $l2->getLanguageFromAcceptLanguage('de-de'));
    }

    public function testParseLanguageFile()
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        $result = [];
        $l->parseLanguageFile($GLOBALS['sys_incdir'] . '/en_US/only-comments.tab', $result);
        $this->assertEquals(
            [
            ],
            $result,
            'Comments are ignored'
        );

        $result = [];
        $l->parseLanguageFile($GLOBALS['sys_incdir'] . '/en_US/empty-lines.tab', $result);
        $this->assertEquals(
            [
            ],
            $result,
            'Empty lines are ignored'
        );

        $result = [
            'file' => ['key1' => 'old-value']
        ];
        $l->parseLanguageFile($GLOBALS['sys_incdir'] . '/en_US/file.tab', $result);
        $this->assertEquals(
            [
                'file' => [
                    'key1' => 'value',
                    'key2' => 'value'
                ]
            ],
            $result,
            'Definitions are merged'
        );
    }

    public function testLoadAllTabFiles()
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        $result = [];
        $l->loadAllTabFiles($GLOBALS['sys_incdir'] . '/en_US/', $result);
        $this->assertEquals(
            [
                'file'   => [
                    'key1' => 'value',
                    'key2' => 'value'
                ],
                'system' => ['locale_label' => 'English'],
                'inc'    => ['key1' => 'value'],
                'common' => ['key1' => 'value'],
            ],
            $result
        );
    }

    public function testDirectories()
    {
        $result = [];

        $l1 = Mockery::mock(BaseLanguage::class)->makePartial();
        $l1->shouldReceive('loadAllTabFiles')->with($GLOBALS['sys_incdir'] . '/en_US', Mockery::any())->once();
        $l1->loadCoreSiteContent('en_US', $result);

        $l2 = Mockery::mock(BaseLanguage::class)->makePartial();
        $l2->shouldReceive('loadAllTabFiles')->with($GLOBALS['sys_custom_incdir'] . '/en_US', Mockery::any())->once();
        $l2->loadCustomSiteContent('en_US', $result);

        $l3 = Mockery::mock(BaseLanguage::class)->makePartial();
        $l3->shouldReceive('loadAllTabFiles')->with(
            $GLOBALS['sys_pluginsroot'] . '/toto/site-content/en_US',
            Mockery::any()
        )->once();
        $l3->loadPluginsSiteContent('en_US', $result);

        $l4 = Mockery::mock(BaseLanguage::class)->makePartial();
        $l4->shouldReceive('loadAllTabFiles')->with(
            $GLOBALS['sys_custompluginsroot'] . '/toto/site-content/en_US',
            Mockery::any()
        )->once();
        $l4->loadPluginsCustomSiteContent('en_US', $result);
    }

    public function testLoadOrder()
    {
        $result = [];

        $l = Mockery::mock(BaseLanguage::class)->makePartial();
        $l->shouldReceive('loadAllTabFiles')->with($GLOBALS['sys_incdir'] . '/en_US', Mockery::any())->once()->ordered();
        $l->shouldReceive('loadAllTabFiles')->with($GLOBALS['sys_custom_incdir'] . '/en_US', Mockery::any())->once()->ordered();
        $l->shouldReceive('loadAllTabFiles')->with($GLOBALS['sys_pluginsroot'] . '/toto/site-content/en_US', Mockery::any())->once()->ordered();
        $l->shouldReceive('loadAllTabFiles')->with($GLOBALS['sys_custompluginsroot'] . '/toto/site-content/en_US', Mockery::any())->once()->ordered();

        $l->loadAllLanguageFiles('en_US', $result);
    }

    public function testDumpLanguageFile()
    {
        $l = new BaseLanguage('en_US', 'en_US');
        $l->dumpLanguageFile('my_lang', array('module' => array('key' => 'value')));
        $stuff = require $this->cache_dir . '/lang/my_lang.php';
        $this->assertEquals('value', $stuff['module']['key']);
    }

    public function testItReturnsLocalisedLanguages()
    {
        $language = new BaseLanguage('en_US,fr_FR,ja_JP', 'en_US');

        $this->assertEquals(
            array(
                'en_US' => 'English',
                'fr_FR' => 'Français',
                'ja_JP' => '日本語'
            ),
            $language->getLanguages()
        );
    }

    public function testItReturnsTrueWhenTheKeyIsPresent()
    {
        $user = \Mockery::mock(PFUser::class, ['getLocale' => 'en_US']);
        $user_manager = \Mockery::mock(UserManager::class, ['getCurrentUser' => $user]);
        UserManager::setInstance($user_manager);

        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        $this->assertTrue($l->hasText('common', 'key1'));
    }

    public function testItReturnsFalseWhenTheKeyIsNotPresent()
    {
        $user = \Mockery::mock(PFUser::class, ['getLocale' => 'en_US']);
        $user_manager = \Mockery::mock(UserManager::class, ['getCurrentUser' => $user]);
        UserManager::setInstance($user_manager);

        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        $this->assertFalse($l->hasText('common', 'missing_key'));
    }

    public function itLoadLangFromExtraPath()
    {
        $extra_path = __DIR__ . '/_fixtures/extra_path';
        ForgeConfig::set('sys_extra_plugin_path', $extra_path);

        $language = \Mockery::mock(BaseLanguage::class)->makePartial();

        $language->shouldReceive('loadAllTabFiles')->with($extra_path . '/bla/site-content/en_US', \Mockery::any())->once()->ordered();
        $language->shouldReceive('loadAllTabFiles')->with(ForgeConfig::get('sys_pluginsroot') . '/toto/site-content/en_US', \Mockery::any())->once()->ordered();
        $language->loadPluginsSiteContent('en_US', []);
    }
}
