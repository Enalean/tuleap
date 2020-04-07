<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use ForgeConfig;
use MediawikiLanguageManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require 'bootstrap.php';

class MediawikiLanguageManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MediawikiLanguageManager */
    private $language_manager;

    /** @var Project */
    private $project;

    /** @var MediawikiLanguageDao */
    private $dao;

    public function setUp(): void
    {
        parent::setUp();
        $this->dao              = \Mockery::spy(\MediawikiLanguageDao::class);
        $this->language_manager = new MediawikiLanguageManager($this->dao);
        $this->project          = \Mockery::spy(\Project::class);

        $this->project->shouldReceive('getID')->andReturn(123);
        ForgeConfig::store();
    }

    public function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testItReturnsProjectLanguageWhenItIsSet()
    {
        $this->dao->shouldReceive('getUsedLanguageForProject')->andReturn(array('language' => 'ja_JP'));

        $this->assertSame($this->language_manager->getUsedLanguageForProject($this->project), 'ja_JP');
    }

    public function testItUsesTheSystemLangIfThereIsOnlyOneAndNoProjectLanguage()
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        $this->dao->shouldReceive('getUsedLanguageForProject')->andReturn(false);

        $this->assertSame($this->language_manager->getUsedLanguageForProject($this->project), 'it_IT');
    }

    public function testItUsesTheSystemLangIfThereIsOnlyOneAndNullProjectLanguage()
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        $this->dao->shouldReceive('getUsedLanguageForProject')->andReturn(array('language' => null));

        $this->assertSame($this->language_manager->getUsedLanguageForProject($this->project), 'it_IT');
    }

    public function testItSavesTheSystemLangIfThereIsOnlyOneAndNoProjectLanguage()
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        $this->dao->shouldReceive('getUsedLanguageForProject')->andReturn(false);

        $this->dao->shouldReceive('updateLanguageOption')->with(123, 'it_IT')->once();

        $this->language_manager->getUsedLanguageForProject($this->project);
    }

    public function testItReturnsNullIfThereAreNoProjectLanguageAndMoreThanOneSystemLanguage()
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT,ja_JP');
        $this->dao->shouldReceive('getUsedLanguageForProject')->andReturn(false);

        $this->assertSame($this->language_manager->getUsedLanguageForProject($this->project), null);
    }

    public function testItSavesNothingIfThereAreNoProjectLanguageAndMoreThanOneSystemLanguage()
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT,ja_JP');
        $this->dao->shouldReceive('getUsedLanguageForProject')->andReturn(false);

        $this->dao->shouldReceive('updateLanguageOption')->with(123, 'it_IT')->never();

        $this->language_manager->getUsedLanguageForProject($this->project);
    }
}
