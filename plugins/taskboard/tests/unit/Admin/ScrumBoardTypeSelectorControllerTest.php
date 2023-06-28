<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Taskboard\AgileDashboard;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Taskboard\Admin\ScrumBoardTypeSelectorController;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ScrumBoardTypeSelectorControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TaskboardUsageDao&MockObject $dao;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(150)->build();

        $this->dao = $this->createMock(TaskboardUsageDao::class);
    }

    private function getController(): ScrumBoardTypeSelectorController
    {
        $renderer = $this->createMock(\TemplateRenderer::class);
        $renderer->method('renderToString');

        return new ScrumBoardTypeSelectorController(
            $this->project,
            $this->dao,
            $renderer
        );
    }

    public function testItDoesNothingIfUserDidNotSubmitBoardType(): void
    {
        $this->mockDefaultBoardType("cardwall");

        $request = new \HTTPRequest();

        $this->dao->expects(self::never())->method('deleteBoardTypeByProjectId');
        $this->dao->expects(self::never())->method('updateBoardTypeByProjectId');

        $this->getController()->onSubmitCallback($request);
    }

    public function testItDeletesWhenUserUsesTheTwoBoards(): void
    {
        $this->mockDefaultBoardType("cardwall");

        $request = new \HTTPRequest();
        $request->set('scrum-board-type', 'both');

        $this->dao->expects(self::once())->method('deleteBoardTypeByProjectId')->with(150);

        $this->getController()->onSubmitCallback($request);
    }

    public function testItUpdatesWhenUserUpdatesTheBoardTypeUsedByItsProject(): void
    {
        $this->mockDefaultBoardType("cardwall");

        $request = new \HTTPRequest();
        $request->set('scrum-board-type', 'taskboard');

        $this->dao->expects(self::once())->method('updateBoardTypeByProjectId')->with(150, 'taskboard');

        $this->getController()->onSubmitCallback($request);
    }

    public function testItCreatesWhenUserUsesDoesNotUseBothBoardsAnymore(): void
    {
        $this->mockDefaultBoardType(false);

        $request = new \HTTPRequest();
        $request->set('scrum-board-type', 'taskboard');

        $this->dao->expects(self::once())->method('create')->with(150, 'taskboard');

        $this->getController()->onSubmitCallback($request);
    }

    public function testItDoesUpdateWhenBoardTypeHasNotChanged(): void
    {
        $this->mockDefaultBoardType('cardwall');

        $request = new \HTTPRequest();
        $request->set('scrum-board-type', 'cardwall');

        $this->dao->expects(self::never())->method('create')->with(150, 'taskboard');

        $this->getController()->onSubmitCallback($request);
    }

    private function mockDefaultBoardType(string|false $default_board): void
    {
        $this->dao->method('searchBoardTypeByProjectId')->with(150)->willReturn($default_board);
    }
}
