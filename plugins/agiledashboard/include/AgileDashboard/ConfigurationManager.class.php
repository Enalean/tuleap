<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class AgileDashboard_ConfigurationManager
{

    public const DEFAULT_SCRUM_TITLE  = 'Scrum';
    public const DEFAULT_KANBAN_TITLE = 'Kanban';

    /**
     * @var AgileDashboard_ConfigurationDao
     */
    private $dao;

    public function __construct(AgileDashboard_ConfigurationDao $dao)
    {
        $this->dao = $dao;
    }

    public function kanbanIsActivatedForProject($project_id)
    {
        $row = $this->dao->isKanbanActivated($project_id)->getRow();
        if ($row) {
            return $row['kanban'];
        }

        return false;
    }

    public function scrumIsActivatedForProject($project_id)
    {
        $row = $this->dao->isScrumActivated($project_id)->getRow();
        if ($row) {
            return $row['scrum'];
        }

        return true;
    }

    public function getScrumTitle($project_id)
    {
        $row = $this->dao->getScrumTitle($project_id);

        if ($row) {
            return $row['scrum_title'];
        }

        return self::DEFAULT_SCRUM_TITLE;
    }

    public function getKanbanTitle($project_id)
    {
        $row = $this->dao->getKanbanTitle($project_id);

        if ($row) {
            return $row['kanban_title'];
        }

        return self::DEFAULT_KANBAN_TITLE;
    }

    public function updateConfiguration(
        $project_id,
        $scrum_is_activated,
        $kanban_is_activated,
        $scrum_title,
        $kanban_title
    ) {
        $this->dao->updateConfiguration(
            $project_id,
            $scrum_is_activated,
            $kanban_is_activated,
            $scrum_title,
            $kanban_title
        );
    }

    public function duplicate($project_id, $template_id)
    {
        $this->dao->duplicate($project_id, $template_id);
    }
}
