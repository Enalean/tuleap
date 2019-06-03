<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
use Tuleap\TestManagement\Administration\StepFieldUsageDetector;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneHiddenFieldsetsPostActionException;
use Tuleap\TestManagement\Administration\TrackerNotInProjectException;
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

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    public function __construct(
        Codendi_Request $request,
        Config $config,
        EventManager $event_manager,
        CSRFSynchronizerToken $csrf_token,
        StepFieldUsageDetector $step_field_usage_detector,
        TrackerChecker $tracker_checker
    ) {
        parent::__construct($request, $config, $event_manager);
        $this->csrf_token                = $csrf_token;
        $this->step_field_usage_detector = $step_field_usage_detector;
        $this->tracker_checker           = $tracker_checker;
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

        $campaign_tracker_id = $this->checkTrackerIdForProject(
            $this->request->get('campaign_tracker_id'),
            $this->config->getCampaignTrackerId($this->project),
            false
        );

        $definition_tracker_id = $this->getValidDefinitionTrackerId();

        $execution_tracker_id = $this->checkTrackerIdForProject(
            $this->request->get('test_execution_tracker_id'),
            $this->config->getTestExecutionTrackerId($this->project),
            true
        );

        $issue_tracker_id = $this->checkTrackerIdForProject(
            $this->request->get('issue_tracker_id'),
            $this->config->getIssueTrackerId($this->project),
            false
        );

        if ($campaign_tracker_id === false ||
            $definition_tracker_id === false ||
            $execution_tracker_id === false ||
            $issue_tracker_id === false
        ) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-testmanagement', 'The submitted administration configuration is not valid.'))
            );

            return;
        }

        $this->config->setProjectConfiguration(
            $this->project,
            $campaign_tracker_id,
            $definition_tracker_id,
            $execution_tracker_id,
            $issue_tracker_id
        );
    }

    /**
     * @param int[] $project_tracker_ids
     * @return array
     */
    private function getValidDefinitionTrackerId()
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
            true
        );
    }

    private function checkTrackerIdForProject($submitted_id, $original_id, $must_check_frozen_fields)
    {
        if (! $submitted_id) {
            return $original_id;
        }

        try {
            if ($must_check_frozen_fields) {
                $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
            } else {
                $this->tracker_checker->checkTrackerIsInProject($this->project, $submitted_id);
            }
            return $submitted_id;
        } catch (TrackerNotInProjectException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(dgettext('tuleap-testmanagement', 'The tracker id %1$s is not part of this project'), $submitted_id)
            );

            return $original_id;
        } catch (TrackerHasAtLeastOneFrozenFieldsPostActionException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s uses frozen fields workflow post action'),
                    $submitted_id
                )
            );

            return $original_id;

        } catch (TrackerHasAtLeastOneHiddenFieldsetsPostActionException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s uses hidden fieldsets workflow post action'),
                    $submitted_id
                )
            );

            return $original_id;
        }
    }

    public function getBreadcrumbs()
    {
        return new AdmininistrationBreadcrumbs();
    }
}
