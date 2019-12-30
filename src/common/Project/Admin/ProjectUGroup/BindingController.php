<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Codendi_Request;
use Feedback;
use ProjectHistoryDao;
use ProjectManager;
use ProjectUGroup;
use UGroupBinding;
use UGroupManager;

class BindingController
{
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var UGroupBinding
     */
    private $ugroup_binding;
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var EditBindingUGroupEventLauncher
     */
    private $edit_event_launcher;

    public function __construct(
        ProjectHistoryDao $history_dao,
        ProjectManager $project_manager,
        UGroupManager $ugroup_manager,
        UGroupBinding $ugroup_binding,
        Codendi_Request $request,
        EditBindingUGroupEventLauncher $edit_event_launcher
    ) {
        $this->history_dao         = $history_dao;
        $this->ugroup_binding      = $ugroup_binding;
        $this->request             = $request;
        $this->ugroup_manager      = $ugroup_manager;
        $this->project_manager     = $project_manager;
        $this->edit_event_launcher = $edit_event_launcher;
    }

    public function removeBinding(ProjectUGroup $ugroup)
    {
        $ugroup_id = $ugroup->getId();
        if ($this->ugroup_binding->removeBinding($ugroup_id)) {
            $this->history_dao->groupAddHistory("ugroup_remove_binding", (string) $ugroup_id, $ugroup->getProjectId());
            $this->edit_event_launcher->launch($ugroup);
        }
    }

    public function addBinding(ProjectUGroup $ugroup)
    {
        $project_source_id = $this->request->getValidated('source_project', 'GroupId');
        $ugroup_source_id  = $this->request->get('source_ugroup');
        $is_valid          = $this->ugroup_manager->checkUGroupValidityByGroupId($project_source_id, $ugroup_source_id);
        $project           = $this->project_manager->getProject($project_source_id);

        if (! $project->isActive()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                _('Cannot bind a ugroup with another located on a non active project.')
            );

            return;
        }

        if ($is_valid && $project->userIsAdmin()) {
            if ($this->ugroup_binding->addBinding($ugroup->getId(), $ugroup_source_id)) {
                $this->history_dao->groupAddHistory(
                    "ugroup_add_binding",
                    $ugroup->getId() . ":" . $ugroup_source_id,
                    $ugroup->getProjectId()
                );
                $this->edit_event_launcher->launch($ugroup);
            }
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error')
            );
        }
    }
}
