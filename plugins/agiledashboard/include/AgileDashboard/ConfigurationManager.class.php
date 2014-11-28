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

class AgileDashboard_ConfigurationManager {

    /**
     * @var AgileDashboard_ConfigurationDao
     */
    private $dao;

    public function __construct(AgileDashboard_ConfigurationDao $dao) {
        $this->dao = $dao;
    }

    public function kanbanIsActivatedForProject($project_id) {
        $row = $this->dao->isKanbanActivated($project_id)->getRow();

        if (! $row) {
            return false;
        }

        return $row['kanban'];
    }

    public function scrumIsActivatedForProject($project_id) {
        $row = $this->dao->isScrumActivated($project_id)->getRow();

        if (! $row) {
            return false;
        }

        return $row['scrum'];
    }

    public function updateConfiguration($project_id, $scrum_is_activated, $kanban_is_activated) {
        $this->dao->updateConfiguration(
            $project_id,
            $scrum_is_activated,
            $kanban_is_activated
        );
    }

}
