<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Language;

use ForgeConfig;

class CustomizableContentLoaderTest extends \TuleapTestCase
{
    /**
     * @var CustomizableContentLoader
     */
    private $loader;
    private $us_user;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_incdir', __DIR__.'/_fixtures/customizable_loader/tuleap/site-content');

        $this->loader  = new CustomizableContentLoader();
        $this->us_user = aUser()->withLang('en_US')->build();
        $this->fr_user = aUser()->withLang('fr_FR')->build();
        $this->br_user = aUser()->withLang('pt_BR')->build();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itLoadsBarInEnglishForEnglishUsers()
    {
        $this->assertEqual('hello', $this->loader->getContent($this->us_user, 'foo/bar'));
    }

    public function itLoadsBarInFrenchForFrenchUsers()
    {
        $this->assertEqual('bonjour', $this->loader->getContent($this->fr_user, 'foo/bar'));
    }

    public function itLoadsBarFromLocalEnUSWhenExists()
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__.'/_fixtures/customizable_loader/etc/site-content');
        $this->assertEqual('local hello', $this->loader->getContent($this->us_user, 'foo/bar'));
    }

    public function itLoadsBarFromLocalFrFRWhenExists()
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__.'/_fixtures/customizable_loader/etc/site-content');
        $this->assertEqual('bonjour local', $this->loader->getContent($this->fr_user, 'foo/bar'));
    }

    public function itFallsBackToDefaultEnglishWhenLocaleDoesntExist()
    {
        $this->assertEqual('hello', $this->loader->getContent($this->br_user, 'foo/bar'));
    }

    public function itFallsBackToLocalEnglishWhenLocaleDoesntExist()
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__.'/_fixtures/customizable_loader/etc/site-content');
        $this->assertEqual('local hello', $this->loader->getContent($this->br_user, 'foo/bar'));
    }

    public function itThrowsAnExceptionWhenNoContentIsFound()
    {
        $exceptionCaught = false;
        try {
            $this->loader->getContent($this->br_user, 'bar/foo');
        } catch (CustomContentNotFoundException $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught);
    }
}
