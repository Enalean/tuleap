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

namespace Tuleap\Taskboard\Admin;

use Tuleap\AgileDashboard\Event\IScrumAdminSectionControllers;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsage;
use Tuleap\Taskboard\AgileDashboard\TaskboardUsageDao;

class ScrumBoardTypeSelectorController implements IScrumAdminSectionControllers
{
    private const FIELD_NAME = 'scrum-board-type';

    /**
     * @var String
     */
    public $content;

    /**
     * @var TaskboardUsageDao
     */
    private $dao;

    /**
     * @var \Project
     */
    private $project;

    public function __construct(\Project $project, TaskboardUsageDao $dao, \TemplateRenderer $renderer)
    {
        $this->dao          = $dao;
        $this->project      = $project;

        $presenter     = new ScrumBoardTypeSelectorPresenter($this->getCurrentBoardType());
        $this->content = $renderer->renderToString('board-type-selector', $presenter);
    }

    public function onSubmitCallback(\HTTPRequest $request): void
    {
        $board_type = (string) $request->get(self::FIELD_NAME);
        if (! $board_type) {
            return;
        }

        $current_board_type = $this->getCurrentBoardType();

        if ($current_board_type === $board_type) {
            return;
        }

        $project_id = (int) $this->project->getID();

        if (
            $board_type !== TaskboardUsage::OPTION_CARDWALL_AND_TASKBOARD &&
            $current_board_type === TaskboardUsage::OPTION_CARDWALL_AND_TASKBOARD
        ) {
            $this->dao->create($project_id, $board_type);
        } else {
            $this->updateBoardType($project_id, $board_type);
        }
    }

    private function updateBoardType(int $project_id, $board_type): void
    {
        if ($board_type === TaskboardUsage::OPTION_CARDWALL_AND_TASKBOARD) {
            $this->dao->deleteBoardTypeByProjectId($project_id);
            return;
        }
        $this->dao->updateBoardTypeByProjectId(
            $project_id,
            $board_type
        );
    }

    private function getCurrentBoardType(): string
    {
        $board_type = $this->dao->searchBoardTypeByProjectId((int) $this->project->getID());

        return $board_type !== false ? $board_type : TaskboardUsage::OPTION_CARDWALL_AND_TASKBOARD;
    }
}
