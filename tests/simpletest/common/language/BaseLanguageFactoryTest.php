<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

class BaseLanguageFactoryTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/local.inc');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        $this->supportedLanguages = ForgeConfig::get('sys_supported_languages');
        $this->oldLocale = setlocale(LC_ALL, "0");
        setlocale(LC_ALL, 'fr_FR');
        $GLOBALS['tmp_dir']           = '/tmp/tuleap_cache';
        $GLOBALS['sys_custom_incdir'] = $this->getTmpDir();
        $GLOBALS['sys_incdir']        = $this->getTmpDir();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        setlocale(LC_ALL, $this->oldLocale);
        unset($GLOBALS['tmp_dir']);
        unset($GLOBALS['sys_incdir']);
        unset($GLOBALS['sys_custom_incdir']);
        parent::tearDown();
    }

    public function testFactoryShouldReturnABaseLanguageAccordingToTheLocale()
    {
        $us = new BaseLanguage($this->supportedLanguages, 'en_US');
        $fr = new BaseLanguage($this->supportedLanguages, 'fr_FR');
        $factory = new BaseLanguageFactory();
        $factory->cacheBaseLanguage($us);
        $factory->cacheBaseLanguage($fr);

        $this->assertEqual($us, $factory->getBaseLanguage('en_US'));
        $this->assertEqual($fr, $factory->getBaseLanguage('fr_FR'));
        $this->assertNotEqual($factory->getBaseLanguage('en_US'), $factory->getBaseLanguage('fr_FR'));
    }

    public function testItInstantiatesMissingLanguages()
    {
        $us = new BaseLanguage($this->supportedLanguages, 'en_US');
        $us->loadLanguage('en_US');
        $fr = new BaseLanguage($this->supportedLanguages, 'fr_FR');
        $fr->loadLanguage('fr_FR');
        $factory = new BaseLanguageFactory();

        $this->assertEqual($us, $factory->getBaseLanguage('en_US'));
        $this->assertEqual($fr, $factory->getBaseLanguage('fr_FR'));
        $this->assertNotEqual($factory->getBaseLanguage('en_US'), $factory->getBaseLanguage('fr_FR'));
        $this->assertTrue($factory->getBaseLanguage('en_US') === $factory->getBaseLanguage('en_US'), 'the language should be cached');
    }

    public function testFactoryShouldSetADefaultLanguageForUnknownLocales()
    {
        $default_language = new BaseLanguage($this->supportedLanguages, ForgeConfig::get('sys_lang'));
        $default_language->loadLanguage(ForgeConfig::get('sys_lang'));
        $factory = new BaseLanguageFactory();

        $this->assertEqual($default_language, $factory->getBaseLanguage('unknown_locale'));
    }

    public function testBecauseOfTheLazyLoadingOfLangTheLangDependedOnTheCurrentUser()
    {
        $factory = new BaseLanguageFactory();

        $fr = $factory->getBaseLanguage('fr_FR');
        $this->assertEqual('fr_FR', $fr->lang);

        $us = $factory->getBaseLanguage('en_US');
        $this->assertEqual('en_US', $us->lang);
    }
    public function testDoesnMessUpGlobalState()
    {
        $factory = new BaseLanguageFactory();

        $currentlocale = setlocale(LC_ALL, '0');
        $fr = $factory->getBaseLanguage('fr_FR');
        $this->assertEqual($currentlocale, setlocale(LC_ALL, '0'));
        $us = $factory->getBaseLanguage('en_US');
        $this->assertEqual($currentlocale, setlocale(LC_ALL, '0'));
    }
}
