<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
use MediawikiLanguageDao;
use MediawikiLanguageManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediawikiLanguageManagerTest extends TestCase
{
    private MediawikiLanguageManager $language_manager;

    private Project $project;

    private MediawikiLanguageDao&MockObject $dao;

    public function setUp(): void
    {
        parent::setUp();
        $this->dao              = $this->createMock(MediawikiLanguageDao::class);
        $this->language_manager = new MediawikiLanguageManager($this->dao);
        $this->project          = ProjectTestBuilder::aProject()->withId(123)->build();

        ForgeConfig::store();
    }

    public function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testItReturnsProjectLanguageWhenItIsSet(): void
    {
        $this->dao->method('getUsedLanguageForProject')->willReturn(['language' => 'ja_JP']);

        self::assertSame($this->language_manager->getUsedLanguageForProject($this->project), 'ja_JP');
    }

    public function testItUsesTheSystemLangIfThereIsOnlyOneAndNoProjectLanguage(): void
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        $this->dao->method('getUsedLanguageForProject')->willReturn(false);

        $this->dao->expects($this->once())->method('updateLanguageOption')->with(123, 'it_IT');

        self::assertSame($this->language_manager->getUsedLanguageForProject($this->project), 'it_IT');
    }

    public function testItUsesTheSystemLangIfThereIsOnlyOneAndNullProjectLanguage(): void
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        $this->dao->method('getUsedLanguageForProject')->willReturn(['language' => null]);

        $this->dao->expects($this->once())->method('updateLanguageOption')->with(123, 'it_IT');

        self::assertSame($this->language_manager->getUsedLanguageForProject($this->project), 'it_IT');
    }

    public function testItSavesTheSystemLangIfThereIsOnlyOneAndNoProjectLanguage(): void
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT');
        $this->dao->method('getUsedLanguageForProject')->willReturn(false);

        $this->dao->expects($this->once())->method('updateLanguageOption')->with(123, 'it_IT');

        $this->language_manager->getUsedLanguageForProject($this->project);
    }

    public function testItReturnsNullIfThereAreNoProjectLanguageAndMoreThanOneSystemLanguage(): void
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT,ja_JP');
        $this->dao->method('getUsedLanguageForProject')->willReturn(false);

        self::assertSame($this->language_manager->getUsedLanguageForProject($this->project), null);
    }

    public function testItSavesNothingIfThereAreNoProjectLanguageAndMoreThanOneSystemLanguage(): void
    {
        ForgeConfig::set('sys_supported_languages', 'it_IT,ja_JP');
        $this->dao->method('getUsedLanguageForProject')->willReturn(false);

        $this->dao->expects(self::never())->method('updateLanguageOption')->with(123, 'it_IT');

        $this->language_manager->getUsedLanguageForProject($this->project);
    }
}
