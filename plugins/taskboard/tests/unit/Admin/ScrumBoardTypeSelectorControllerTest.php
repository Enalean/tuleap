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

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Taskboard\Admin\ScrumBoardTypeSelectorController;

class ScrumBoardTypeSelectorControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TaskboardUsageDao | m\MockInterface
     */
    private $dao;

    /**
     * @var \Project | m\MockInterface
     */
    private $project;

    protected function setUp(): void
    {
        $this->project = m::mock(\Project::class);
        $this->project->shouldReceive('getID')->andReturn(150);

        $this->dao = m::mock(TaskboardUsageDao::class);
    }

    private function getController(): ScrumBoardTypeSelectorController
    {
        $renderer = m::mock(\TemplateRenderer::class);
        $renderer->shouldReceive('renderToString');

        return new ScrumBoardTypeSelectorController(
            $this->project,
            $this->dao,
            $renderer
        );
    }

    public function testItDoesNothingIfUserDidNotSubmitBoardType(): void
    {
        $this->mockDefaultBoardType("cardwall");

        $request = m::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('scrum-board-type')->andReturn(false)->once();

        $this->dao->shouldReceive('deleteBoardTypeByProjectId')->never();
        $this->dao->shouldReceive('updateBoardTypeByProjectId')->never();

        $this->getController()->onSubmitCallback($request);
    }

    public function testItDeletesWhenUserUsesTheTwoBoards(): void
    {
        $this->mockDefaultBoardType("cardwall");

        $request = m::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('scrum-board-type')->andReturn('both')->once();

        $this->dao->shouldReceive('deleteBoardTypeByProjectId')->with(150)->once();

        $this->getController()->onSubmitCallback($request);
    }

    public function testItUpdatesWhenUserUpdatesTheBoardTypeUsedByItsProject(): void
    {
        $this->mockDefaultBoardType("cardwall");

        $request = m::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('scrum-board-type')->andReturn('taskboard')->once();

        $this->dao->shouldReceive('updateBoardTypeByProjectId')->with(150, 'taskboard')->once();

        $this->getController()->onSubmitCallback($request);
    }

    public function testItCreatesWhenUserUsesDoesNotUseBothBoardsAnymore(): void
    {
        $this->mockDefaultBoardType(false);

        $request = m::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('scrum-board-type')->andReturn('taskboard')->once();

        $this->dao->shouldReceive('create')->with(150, 'taskboard')->once();

        $this->getController()->onSubmitCallback($request);
    }

    public function testItDoesUpdateWhenBoardTypeHasNotChanged(): void
    {
        $this->mockDefaultBoardType('cardwall');

        $request = m::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->with('scrum-board-type')->andReturn('cardwall')->once();

        $this->dao->shouldReceive('create')->with(150, 'taskboard')->never();

        $this->getController()->onSubmitCallback($request);
    }

    /**
     * @param $default_board string | false
     */
    private function mockDefaultBoardType($default_board): void
    {
        $this->dao->shouldReceive('searchBoardTypeByProjectId')->with(150)->andReturn($default_board);
    }
}
