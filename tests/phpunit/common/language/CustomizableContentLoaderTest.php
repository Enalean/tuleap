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

final class CustomizableContentLoaderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CustomizableContentLoader
     */
    private $loader;
    private $us_user;

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
        $this->assertEquals('hello', $this->loader->getContent($this->us_user, 'foo/bar'));
    }

    public function testItLoadsBarInFrenchForFrenchUsers(): void
    {
        $this->assertEquals('bonjour', $this->loader->getContent($this->fr_user, 'foo/bar'));
    }

    public function testItLoadsBarFromLocalEnUSWhenExists(): void
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/customizable_loader/etc/site-content');
        $this->assertEquals('local hello', $this->loader->getContent($this->us_user, 'foo/bar'));
    }

    public function testItLoadsBarFromLocalFrFRWhenExists(): void
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/customizable_loader/etc/site-content');
        $this->assertEquals('bonjour local', $this->loader->getContent($this->fr_user, 'foo/bar'));
    }

    public function testItFallsBackToDefaultEnglishWhenLocaleDoesntExist(): void
    {
        $this->assertEquals('hello', $this->loader->getContent($this->br_user, 'foo/bar'));
    }

    public function testItFallsBackToLocalEnglishWhenLocaleDoesntExist(): void
    {
        ForgeConfig::set('sys_custom_incdir', __DIR__ . '/_fixtures/customizable_loader/etc/site-content');
        $this->assertEquals('local hello', $this->loader->getContent($this->br_user, 'foo/bar'));
    }

    public function testItThrowsAnExceptionWhenNoContentIsFound(): void
    {
        $this->expectException(CustomContentNotFoundException::class);
        $this->loader->getContent($this->br_user, 'bar/foo');
    }
}
