<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use TrackerFactory;
use Tuleap\TestManagement\Administration\StepFieldUsageDetector;
use Tuleap\TestManagement\Breadcrumbs\AdmininistrationBreadcrumbs;

class AdminController extends TestManagementController
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var StepFieldUsageDetector
     */
    private $step_field_usage_detector;

    public function __construct(
        Codendi_Request $request,
        Config $config,
        TrackerFactory $tracker_factory,
        EventManager $event_manager,
        CSRFSynchronizerToken $csrf_token,
        StepFieldUsageDetector $step_field_usage_detector
    ) {
        parent::__construct($request, $config, $tracker_factory, $event_manager);
        $this->csrf_token                = $csrf_token;
        $this->step_field_usage_detector = $step_field_usage_detector;
    }

    public function admin()
    {
        $test_definition_tracker_id = $this->config->getTestDefinitionTrackerId($this->project);
        $is_definition_disabled = $this->step_field_usage_detector->isStepDefinitionFieldUsed(
            $test_definition_tracker_id
        );

        $test_execution_tracker_id = $this->config->getTestExecutionTrackerId($this->project);
        $is_execution_disabled = $this->step_field_usage_detector->isStepExecutionFieldUsed(
            $test_execution_tracker_id
        );

        return $this->renderToString(
            'admin',
            new AdminPresenter(
                $this->config->getCampaignTrackerId($this->project),
                $test_definition_tracker_id,
                $test_execution_tracker_id,
                $this->config->getIssueTrackerId($this->project),
                $this->csrf_token,
                $is_definition_disabled,
                $is_execution_disabled
            )
        );
    }

    public function update()
    {
        $this->csrf_token->check();
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
            $this->getValidDefinitionTrackerId($project_tracker_ids),
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

    /**
     * @param int[] $project_tracker_ids
     * @return array
     */
    private function getValidDefinitionTrackerId(array $project_tracker_ids)
    {
        $current_tracker_id = $this->config->getTestDefinitionTrackerId($this->project);

        // If StepDefinition field is used, we cannot change the Definition tracker
        // Form input is disabled so we should not get there, but never trust the user's data!
        if ($this->step_field_usage_detector->isStepDefinitionFieldUsed($current_tracker_id)) {
            return $current_tracker_id;
        }

        return $this->checkTrackerIdForProject(
            $this->request->get('test_definition_tracker_id'),
            $current_tracker_id,
            $project_tracker_ids
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

    public function getBreadcrumbs()
    {
        return new AdmininistrationBreadcrumbs();
    }
}
