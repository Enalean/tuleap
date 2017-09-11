<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Feedback;

class AdminController extends TestManagementController
{

    public function admin()
    {
        return $this->renderToString(
            'admin',
            new AdminPresenter(
                $this->config->getCampaignTrackerId($this->project),
                $this->config->getTestDefinitionTrackerId($this->project),
                $this->config->getTestExecutionTrackerId($this->project),
                $this->config->getIssueTrackerId($this->project)
            )
        );
    }

    public function update()
    {
        $project_trackers    = $this->tracker_factory->getTrackersByGroupId($this->project->getId());
        $project_tracker_ids = array_map(
            function ($tracker) {
                return $tracker->getId();
            },
            $project_trackers
        );

        $this->config->setProjectConfiguration(
            $this->project,
            $this->checkTrackerIdForProject(
                $this->request->get('campaign_tracker_id'),
                $this->config->getCampaignTrackerId($this->project),
                $project_tracker_ids
            ),
            $this->checkTrackerIdForProject(
                $this->request->get('test_definition_tracker_id'),
                $this->config->getTestDefinitionTrackerId($this->project),
                $project_tracker_ids
            ),
            $this->checkTrackerIdForProject(
                $this->request->get('test_execution_tracker_id'),
                $this->config->getTestExecutionTrackerId($this->project),
                $project_tracker_ids
            ),
            $this->checkTrackerIdForProject(
                $this->request->get('issue_tracker_id'),
                $this->config->getIssueTrackerId($this->project),
                $project_tracker_ids
            )
        );
    }

    private function checkTrackerIdForProject($submitted_id, $original_id, $project_tracker_ids)
    {
        $is_valid_project_tracker_id = in_array($submitted_id, $project_tracker_ids);
        if (! $is_valid_project_tracker_id) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText(
                    'plugin_testmanagement',
                    'invalid_tracker_id_for_project',
                    $submitted_id
                )
            );
        }

        return $is_valid_project_tracker_id ? $submitted_id : $original_id;
    }
}
