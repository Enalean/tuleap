<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

final class BaseLanguageFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\TemporaryTestDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/local.inc');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        $this->supportedLanguages = ForgeConfig::get('sys_supported_languages');
        $this->oldLocale = setlocale(LC_ALL, "0");
        setlocale(LC_ALL, 'fr_FR');
        $GLOBALS['tmp_dir']               = $this->getTmpDir() . '/tuleap_cache';
        $GLOBALS['sys_custom_incdir']     = $this->getTmpDir();
        $GLOBALS['sys_incdir']            = $this->getTmpDir();
        $GLOBALS['sys_custompluginsroot'] = $this->getTmpDir();
    }

    protected function tearDown(): void
    {
        setlocale(LC_ALL, $this->oldLocale);
        unset($GLOBALS['tmp_dir'], $GLOBALS['sys_incdir'], $GLOBALS['sys_custom_incdir'], $GLOBALS['sys_custompluginsroot']);
        parent::tearDown();
    }

    public function testFactoryShouldReturnABaseLanguageAccordingToTheLocale(): void
    {
        $us = new BaseLanguage($this->supportedLanguages, 'en_US');
        $fr = new BaseLanguage($this->supportedLanguages, 'fr_FR');
        $factory = new BaseLanguageFactory();
        $factory->cacheBaseLanguage($us);
        $factory->cacheBaseLanguage($fr);

        $this->assertEquals($us, $factory->getBaseLanguage('en_US'));
        $this->assertEquals($fr, $factory->getBaseLanguage('fr_FR'));
        $this->assertNotEquals($factory->getBaseLanguage('en_US'), $factory->getBaseLanguage('fr_FR'));
    }

    public function testItInstantiatesMissingLanguages(): void
    {
        $us = new BaseLanguage($this->supportedLanguages, 'en_US');
        $us->loadLanguage('en_US');
        $fr = new BaseLanguage($this->supportedLanguages, 'fr_FR');
        $fr->loadLanguage('fr_FR');
        $factory = new BaseLanguageFactory();

        $this->assertEquals($us, $factory->getBaseLanguage('en_US'));
        $this->assertEquals($fr, $factory->getBaseLanguage('fr_FR'));
        $this->assertNotEquals($factory->getBaseLanguage('en_US'), $factory->getBaseLanguage('fr_FR'));
        $this->assertSame($factory->getBaseLanguage('en_US'), $factory->getBaseLanguage('en_US'), 'the language should be cached');
    }

    public function testFactoryShouldSetADefaultLanguageForUnknownLocales(): void
    {
        $default_language = new BaseLanguage($this->supportedLanguages, ForgeConfig::get('sys_lang'));
        $default_language->loadLanguage(ForgeConfig::get('sys_lang'));
        $factory = new BaseLanguageFactory();

        $this->assertEquals($default_language, $factory->getBaseLanguage('unknown_locale'));
    }

    public function testBecauseOfTheLazyLoadingOfLangTheLangDependedOnTheCurrentUser(): void
    {
        $factory = new BaseLanguageFactory();

        $fr = $factory->getBaseLanguage('fr_FR');
        $this->assertEquals('fr_FR', $fr->lang);

        $us = $factory->getBaseLanguage('en_US');
        $this->assertEquals('en_US', $us->lang);
    }
    public function testDoesntMessUpGlobalState(): void
    {
        $factory = new BaseLanguageFactory();

        $currentlocale = setlocale(LC_ALL, '0');
        $factory->getBaseLanguage('fr_FR');
        $this->assertEquals($currentlocale, setlocale(LC_ALL, '0'));
        $factory->getBaseLanguage('en_US');
        $this->assertEquals($currentlocale, setlocale(LC_ALL, '0'));
    }
}
