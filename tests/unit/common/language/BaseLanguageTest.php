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

declare(strict_types=1);

use Tuleap\Test\Builders\UserTestBuilder;

class BaseLanguageTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\ForgeConfigSandbox;

    protected $cache_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache_dir = $this->getTmpDir();

        ForgeConfig::set('sys_pluginsroot', __DIR__ . '/_fixtures/tuleap/plugins');
        ForgeConfig::set('sys_extra_plugin_path', '');
        ForgeConfig::set('sys_incdir', __DIR__ . '/_fixtures/tuleap/site-content');
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/etc/site-content');
        ForgeConfig::set('sys_custompluginsroot', __DIR__ . '/_fixtures/etc/plugins');

        ForgeConfig::set('codendi_cache_dir', $this->cache_dir);
        if (! is_dir($this->cache_dir . '/lang')) {
            mkdir($this->cache_dir . '/lang', 0777, true);
        }
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function testConstructorIsOkWhenLanguagesAreKnown(): void
    {
        $l1 = new BaseLanguage(',lang1,lang2, lang3 ,lang4 , lang5,', 'lang1');
        self::assertEquals(['lang1', 'lang2', 'lang3', 'lang4', 'lang5'], $l1->allLanguages);
    }

    public function testConstructorThrowExceptionWhenLanguageNotSupported(): void
    {
        self::expectExceptionMessage('The default language must be part of supported languages');
        new BaseLanguage('lang1,lang2', 'do-not-exist');
    }

    public function testConstructorThrowExceptionWhenNoLanguageProvided(): void
    {
        self::expectExceptionMessage('You must provide supported languages (see local.inc)');
        new BaseLanguage('', '');
    }

    public function testParseAcceptLanguage(): void
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        self::assertEquals(
            [
                'en-ca' => 1,
                'en'    => 0.8,
                'en-us' => 0.6,
                'de-de' => 0.4,
                'de'    => 0.2,
            ],
            $l->parseAcceptLanguage('en-ca,en;q=0.8,en-us;q=0.6,de-de;q=0.4,de;q=0.2')
        );

        self::assertEquals(
            [
                'en-us' => 1,
                'en'    => 0.8,
                'fr'    => 0.5,
                'fr-fr' => 0.3,
            ],
            $l->parseAcceptLanguage('en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3')
        );

        self::assertEquals([], $l->parseAcceptLanguage(''));
    }

    public function testGetLanguageFromAcceptLanguage(): void
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        self::assertEquals('en_US', $l->getLanguageFromAcceptLanguage(''));
        self::assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en'));
        self::assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en-us'));
        self::assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en-ca'));
        self::assertEquals('en_US', $l->getLanguageFromAcceptLanguage('en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3'));
        self::assertEquals('en_US', $l->getLanguageFromAcceptLanguage('de-de'));
        self::assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr'));
        self::assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr-fr'));
        self::assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr-ca'));
        self::assertEquals('fr_FR', $l->getLanguageFromAcceptLanguage('fr-fr,fr;q=0.8,en-us;q=0.5,en;q=0.3'));

        $l2 = new BaseLanguage('en_US,fr_FR', 'fr_FR');
        self::assertEquals('fr_FR', $l2->getLanguageFromAcceptLanguage(''));
        self::assertEquals('fr_FR', $l2->getLanguageFromAcceptLanguage('de-de'));
    }

    public function testParseLanguageFile(): void
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        $result = [];
        $l->parseLanguageFile(ForgeConfig::get('sys_incdir') . '/en_US/only-comments.tab', $result);
        self::assertEquals(
            [
            ],
            $result,
            'Comments are ignored'
        );

        $result = [];
        $l->parseLanguageFile(ForgeConfig::get('sys_incdir') . '/en_US/empty-lines.tab', $result);
        self::assertEquals(
            [
            ],
            $result,
            'Empty lines are ignored'
        );

        $result = [
            'file' => ['key1' => 'old-value'],
        ];
        $l->parseLanguageFile(ForgeConfig::get('sys_incdir') . '/en_US/file.tab', $result);
        self::assertEquals(
            [
                'file' => [
                    'key1' => 'value',
                    'key2' => 'value',
                ],
            ],
            $result,
            'Definitions are merged'
        );
    }

    public function testLoadAllTabFiles(): void
    {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');

        $result = [];
        $l->loadAllTabFiles(ForgeConfig::get('sys_incdir') . '/en_US/', $result);
        self::assertEquals(
            [
                'file'   => [
                    'key1' => 'value',
                    'key2' => 'value',
                ],
                'system' => ['locale_label' => 'English'],
                'inc'    => ['key1' => 'value'],
                'common' => ['key1' => 'value'],
            ],
            $result
        );
    }

    public function testDirectories(): void
    {
        $result = [];

        $l1 = $this->createPartialMock(BaseLanguage::class, [
            'loadAllTabFiles',
        ]);
        $l1->expects(self::once())->method('loadAllTabFiles')->with(ForgeConfig::get('sys_incdir') . '/en_US', self::anything());
        $l1->loadCoreSiteContent('en_US', $result);

        $l2 = $this->createPartialMock(BaseLanguage::class, [
            'loadAllTabFiles',
        ]);
        $l2->expects(self::once())->method('loadAllTabFiles')->with(ForgeConfig::get('sys_custom_incdir') . '/en_US', self::anything());
        $l2->loadCustomSiteContent('en_US', $result);

        $l3 = $this->createPartialMock(BaseLanguage::class, [
            'loadAllTabFiles',
        ]);
        $l3->expects(self::once())->method('loadAllTabFiles')->with(
            ForgeConfig::get('sys_pluginsroot') . '/toto/site-content/en_US',
            self::anything()
        );
        $l3->loadPluginsSiteContent('en_US', $result);

        $l4 = $this->createPartialMock(BaseLanguage::class, [
            'loadAllTabFiles',
        ]);
        $l4->expects(self::once())->method('loadAllTabFiles')->with(
            ForgeConfig::get('sys_custompluginsroot') . '/toto/site-content/en_US',
            self::anything()
        );
        $l4->loadPluginsCustomSiteContent('en_US', $result);
    }

    public function testLoadOrder(): void
    {
        $result = [];

        $l = $this->createPartialMock(BaseLanguage::class, [
            'loadAllTabFiles',
        ]);
        $l->expects(self::exactly(4))->method('loadAllTabFiles')
            ->withConsecutive(
                [ForgeConfig::get('sys_incdir') . '/en_US', self::anything()],
                [ForgeConfig::get('sys_custom_incdir') . '/en_US', self::anything()],
                [ForgeConfig::get('sys_pluginsroot') . '/toto/site-content/en_US', self::anything()],
                [ForgeConfig::get('sys_custompluginsroot') . '/toto/site-content/en_US', self::anything()]
            );

        $l->loadAllLanguageFiles('en_US', $result);
    }

    public function testDumpLanguageFile(): void
    {
        $l = new BaseLanguage('en_US', 'en_US');
        $l->dumpLanguageFile('my_lang', ['module' => ['key' => 'value']]);
        $stuff = require $this->cache_dir . '/lang/my_lang.php';
        self::assertEquals('value', $stuff['module']['key']);
    }

    public function testItReturnsLocalisedLanguages(): void
    {
        $language = new BaseLanguage('en_US,fr_FR,ja_JP', 'en_US');

        self::assertEquals(
            [
                'en_US' => 'English',
                'fr_FR' => 'Français',
                'ja_JP' => '日本語',
            ],
            $language->getLanguages()
        );
    }

    public function testItReturnsTrueWhenTheKeyIsPresent(): void
    {
        $user         = UserTestBuilder::anActiveUser()->withLocale('en_US')->build();
        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn($user);
        UserManager::setInstance($user_manager);

        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        self::assertTrue($l->hasText('common', 'key1'));
    }

    public function testItReturnsFalseWhenTheKeyIsNotPresent(): void
    {
        $user         = UserTestBuilder::anActiveUser()->withLocale('en_US')->build();
        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn($user);
        UserManager::setInstance($user_manager);

        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        self::assertFalse($l->hasText('common', 'missing_key'));
    }

    public function testItLoadLangFromExtraPath(): void
    {
        $extra_path = __DIR__ . '/_fixtures/extra_path';
        ForgeConfig::set('sys_extra_plugin_path', $extra_path);

        $language = $this->createPartialMock(BaseLanguage::class, [
            'loadAllTabFiles',
        ]);

        $language->expects(self::exactly(2))->method('loadAllTabFiles')
            ->withConsecutive(
                [$extra_path . '/bla/site-content/en_US', self::anything()],
                [ForgeConfig::get('sys_pluginsroot') . '/toto/site-content/en_US', self::anything()]
            );
        $array = [];
        $language->loadPluginsSiteContent('en_US', $array);
    }
}
