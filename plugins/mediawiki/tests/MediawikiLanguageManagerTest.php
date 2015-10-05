<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require 'bootstrap.php';
require_once dirname(__FILE__).'/../include/MediawikiLanguageDao.php';
require_once dirname(__FILE__).'/../include/MediawikiLanguageManager.php';
require_once dirname(__FILE__).'/../include/ServiceMediawiki.class.php';

class MediawikiLanguageManagerTest extends TuleapTestCase {

    /** @var MediawikiLanguageManager */
    private $language_manager;

    /** @var Project */
    private $project;

    /** @var MediawikiLanguageDao */
    private $dao;

    public function setUp() {
        parent::setUp();
        $this->dao                  = mock('MediawikiLanguageDao');
        $this->language_manager     = new MediawikiLanguageManager($this->dao);
        $this->project              = stub('Project')->getID()->returns(123);
        ForgeConfig::store();
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itReturnsProjectLanguageWhenItIsSet() {
        stub($this->dao)->getUsedLanguageForProject()->returns(array('language' => 'ja_JP'));

        $this->assertEqual($this->language_manager->getUsedLanguageForProject($this->project), 'ja_JP');
    }

    public function itUsesTheSystemLangIfThereIsOnlyOneAndNoProjectLanguage() {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        stub($this->dao)->getUsedLanguageForProject()->returns(false);

        $this->assertEqual($this->language_manager->getUsedLanguageForProject($this->project), 'it_IT');
    }

    public function itUsesTheSystemLangIfThereIsOnlyOneAndNullProjectLanguage() {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        stub($this->dao)->getUsedLanguageForProject()->returns(array('language' => null));

        $this->assertEqual($this->language_manager->getUsedLanguageForProject($this->project), 'it_IT');
    }

    public function itSavesTheSystemLangIfThereIsOnlyOneAndNoProjectLanguage() {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        stub($this->dao)->getUsedLanguageForProject()->returns(false);

        expect($this->dao)->updateLanguageOption(123, 'it_IT')->once();

        $this->language_manager->getUsedLanguageForProject($this->project);
    }

    public function itReturnsNullIfThereAreNoProjectLanguageAndMoreThanOneSystemLanguage() {
        ForgeConfig::set('sys_supported_languages', 'it_IT,ja_JP');
        stub($this->dao)->getUsedLanguageForProject()->returns(false);

        $this->assertEqual($this->language_manager->getUsedLanguageForProject($this->project), null);
    }

    public function itSavesNothingIfThereAreNoProjectLanguageAndMoreThanOneSystemLanguage() {
        ForgeConfig::set('sys_supported_languages', 'it_IT,ja_JP');
        stub($this->dao)->getUsedLanguageForProject()->returns(false);

        expect($this->dao)->updateLanguageOption()->never();

        $this->language_manager->getUsedLanguageForProject($this->project);
    }
}
