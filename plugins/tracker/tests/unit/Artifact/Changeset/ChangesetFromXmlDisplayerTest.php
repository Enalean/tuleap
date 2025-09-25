<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use PHPUnit\Framework\MockObject\MockObject;
use TemplateRenderer;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ChangesetFromXmlDisplayerTest extends TestCase
{
    use GlobalLanguageMock;

    private ChangesetFromXmlDisplayer $displayer;
    private TemplateRenderer&MockObject $renderer;
    private UserManager&MockObject $user_manager;
    private ChangesetFromXmlDao&MockObject $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao          = $this->createMock(ChangesetFromXmlDao::class);
        $this->user_manager = $this->createMock(UserManager::class);
        $this->renderer     = $this->createMock(TemplateRenderer::class);
        $this->displayer    = new ChangesetFromXmlDisplayer(
            $this->dao,
            $this->user_manager,
            $this->renderer
        );

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testItReturnsAnEmptyStringIfChangesetDoesNotComeFromXmlImport(): void
    {
        $this->dao->method('searchChangeset')->willReturn(null);
        $this->user_manager->expects($this->never())->method('getUserById');
        $this->renderer->expects($this->never())->method('renderToString');

        self::assertEquals('', $this->displayer->display(1234));
    }

    public function testItReturnsAnEmptyStringIfUserWhoPerfomTheImportDoesNotExisit(): void
    {
        $this->dao->method('searchChangeset')->willReturn(['user_id' => 101, 'timestamp' => 123456789]);
        $this->user_manager->expects($this->once())->method('getUserById')->willReturn(null);
        $this->renderer->expects($this->never())->method('renderToString');

        self::assertEquals('', $this->displayer->display(1234));
    }

    public function testItRendersTheChangeset(): void
    {
        $this->dao->method('searchChangeset')->willReturn(['user_id' => 101, 'timestamp' => 123456789]);
        $this->user_manager->expects($this->once())->method('getUserById')->willReturn(UserTestBuilder::aUser()->withUserName('user')->build());
        $this->renderer->expects($this->once())->method('renderToString')->willReturn('Imported by user on 2020-04-20');

        self::assertEquals('Imported by user on 2020-04-20', $this->displayer->display(1234));
    }
}
