<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Language;

use ForgeConfig;
use PFUser;

final class CustomizableContentLoaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CustomizableContentLoader $loader;
    private PFUser $us_user;
    private PFUser $fr_user;
    private PFUser $br_user;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_incdir', __DIR__ . '/_fixtures/customizable_loader/tuleap/site-content');

        $this->loader  = new CustomizableContentLoader();
        $this->us_user = new PFUser([
            'language_id' => 'en_US',
        ]);
        $this->fr_user = new PFUser([
            'language_id' => 'fr_FR',
        ]);
        $this->br_user = new PFUser([
            'language_id' => 'pt_BR',
        ]);
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testItLoadsBarInEnglishForEnglishUsers(): void
    {
        self::assertEquals('hello', $this->loader->getContent($this->us_user, 'foo/bar'));
    }

    public function testItLoadsBarInFrenchForFrenchUsers(): void
    {
        self::assertEquals('bonjour', $this->loader->getContent($this->fr_user, 'foo/bar'));
    }

    public function testItLoadsBarFromLocalEnUSWhenExists(): void
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/customizable_loader/etc/site-content');
        self::assertEquals('local hello', $this->loader->getContent($this->us_user, 'foo/bar'));
    }

    public function testItLoadsBarFromLocalFrFRWhenExists(): void
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/customizable_loader/etc/site-content');
        self::assertEquals('bonjour local', $this->loader->getContent($this->fr_user, 'foo/bar'));
    }

    public function testItFallsBackToDefaultEnglishWhenLocaleDoesntExist(): void
    {
        self::assertEquals('hello', $this->loader->getContent($this->br_user, 'foo/bar'));
    }

    public function testItFallsBackToLocalEnglishWhenLocaleDoesntExist(): void
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/customizable_loader/etc/site-content');
        self::assertEquals('local hello', $this->loader->getContent($this->br_user, 'foo/bar'));
    }

    public function testItThrowsAnExceptionWhenNoContentIsFound(): void
    {
        self::expectException(CustomContentNotFoundException::class);
        $this->loader->getContent($this->br_user, 'bar/foo');
    }
}
