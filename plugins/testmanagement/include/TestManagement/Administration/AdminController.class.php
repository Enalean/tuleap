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

namespace Tuleap\TestManagement\Administration;

use Codendi_Request;
use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use Tuleap\TestManagement\Breadcrumbs\AdmininistrationBreadcrumbs;
use Tuleap\TestManagement\Breadcrumbs\Breadcrumbs;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\TestManagement\TestManagementController;
use Tuleap\TestManagement\TestmanagementTrackersConfigurator;
use Tuleap\TestManagement\TrackerDefinitionNotValidException;
use Tuleap\TestManagement\TrackerExecutionNotValidException;
use Valid_UInt;

class AdminController extends TestManagementController
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var FieldUsageDetector
     */
    private $field_usage_detector;

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    /**
     * @var Valid_UInt
     */
    private $int_validator;
    /**
     * @var AdminTrackersRetriever
     */
    private $tracker_retriever;

    public function __construct(
        Codendi_Request $request,
        Config $config,
        EventManager $event_manager,
        CSRFSynchronizerToken $csrf_token,
        FieldUsageDetector $field_usage_detector,
        TrackerChecker $tracker_checker,
        Valid_UInt $int_validator,
        AdminTrackersRetriever $tracker_retriever,
    ) {
        parent::__construct($request, $config, $event_manager);
        $this->csrf_token           = $csrf_token;
        $this->field_usage_detector = $field_usage_detector;
        $this->tracker_checker      = $tracker_checker;
        $this->int_validator        = $int_validator;
        $this->tracker_retriever    = $tracker_retriever;
    }

    public function admin(): string
    {
        $campaign_trackers        = $this->tracker_retriever->retrieveAvailableTrackersForCampaign($this->project);
        $issue_trackers           = $this->tracker_retriever->retrieveAvailableTrackersForIssue($this->project);
        $test_definition_trackers = $this->tracker_retriever->retrieveAvailableTrackersForDefinition($this->project);
        $test_execution_trackers  = $this->tracker_retriever->retrieveAvailableTrackersForExecution($this->project);

        $is_definition_disabled = $test_definition_trackers->selected_tracker !== null
            && $this->field_usage_detector->isStepDefinitionFieldUsed(
                $test_definition_trackers->selected_tracker->tracker_id
            );

        $is_execution_disabled = $test_execution_trackers->selected_tracker !== null
            && $this->field_usage_detector->isStepExecutionFieldUsed(
                $test_execution_trackers->selected_tracker->tracker_id
            );

        return $this->renderToString(
            'admin',
            new AdminPresenter(
                $campaign_trackers,
                $test_definition_trackers,
                $test_execution_trackers,
                $issue_trackers,
                $this->csrf_token,
                $is_definition_disabled,
                $is_execution_disabled
            )
        );
    }

    public function update(): void
    {
        $this->csrf_token->check();

        $campaign_tracker_id = $this->checkTrackerIdForProject(
            $this->request->get('campaign_tracker_id'),
            $this->config->getCampaignTrackerId($this->project),
            TestmanagementTrackersConfigurator::CAMPAIGN_TRACKER_NAME
        );

        $definition_tracker_id = $this->getValidDefinitionTrackerId();

        $execution_tracker_id = $this->checkTrackerIdForProject(
            $this->request->get('test_execution_tracker_id'),
            $this->config->getTestExecutionTrackerId($this->project),
            TestmanagementTrackersConfigurator::EXECUTION_TRACKER_NAME
        );

        $issue_tracker_id = $this->checkIssueTrackerIdForProject(
            $this->request->get('issue_tracker_id'),
            (string) $this->config->getIssueTrackerId($this->project),
            TestmanagementTrackersConfigurator::ISSUE_TRACKER_NAME
        );

        if (
            $campaign_tracker_id === false ||
            $campaign_tracker_id === null ||
            $definition_tracker_id === false ||
            $definition_tracker_id === null ||
            $execution_tracker_id === false ||
            $execution_tracker_id === null ||
            $issue_tracker_id === false
        ) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-testmanagement', 'The submitted administration configuration is not valid.'),
            );

            return;
        }

        $this->config->setProjectConfiguration(
            $this->project,
            (int) $campaign_tracker_id,
            (int) $definition_tracker_id,
            (int) $execution_tracker_id,
            (int) $issue_tracker_id
        );
    }

    /**
     * @return false|int|string|null
     */
    private function getValidDefinitionTrackerId()
    {
        $current_tracker_id = $this->config->getTestDefinitionTrackerId($this->project);

        // If StepDefinition field is used, we cannot change the Definition tracker
        // Form input is disabled so we should not get there, but never trust the user's data!
        if ($this->field_usage_detector->isStepDefinitionFieldUsed((int) $current_tracker_id)) {
            return $current_tracker_id;
        }

        return $this->checkTrackerIdForProject(
            $this->request->get('test_definition_tracker_id'),
            $current_tracker_id,
            TestmanagementTrackersConfigurator::DEFINITION_TRACKER_NAME
        );
    }

    /**
     * @return false|int|null|string
     */
    private function checkIssueTrackerIdForProject(
        ?string $submitted_id,
        ?string $original_id,
        string $tracker_type,
    ) {
        if ($submitted_id === '') {
            return null;
        }

        return $this->checkTrackerIdForProject($submitted_id, $original_id, $tracker_type);
    }

    /**
     * @param false|int|null|string $original_id
     *
     * @return false|int|null|string
     */
    private function checkTrackerIdForProject(?string $submitted_id, $original_id, string $tracker_type)
    {
        try {
            $this->checkIdProvidedValidity($submitted_id);

            if (! $submitted_id || (int) $submitted_id === $original_id) {
                return $original_id;
            }

            if ($tracker_type === TestmanagementTrackersConfigurator::DEFINITION_TRACKER_NAME) {
                $this->tracker_checker->checkSubmittedDefinitionTrackerCanBeUsed($this->project, (int) $submitted_id);
            } elseif ($tracker_type === TestmanagementTrackersConfigurator::EXECUTION_TRACKER_NAME) {
                $this->tracker_checker->checkSubmittedExecutionTrackerCanBeUsed($this->project, (int) $submitted_id);
            } else {
                $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, (int) $submitted_id);
            }
            return $submitted_id;
        } catch (TrackerNotInProjectException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s is not part of this project'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (MissingArtifactLinkException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s does not have artifact links field'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (TrackerDefinitionNotValidException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s does not have step definition field'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (TrackerExecutionNotValidException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s does not have step execution field'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (InvalidTrackerIdProvidedException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s is not a valid id'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (TrackerIsDeletedException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s is deleted'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (TrackerDoesntExistException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s not found'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (TrackerHasAtLeastOneFrozenFieldsPostActionException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s uses frozen fields workflow post action'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        } catch (TrackerHasAtLeastOneHiddenFieldsetsPostActionException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'The tracker id %1$s uses hidden fieldsets workflow post action'),
                    (string) $submitted_id
                )
            );

            return $original_id;
        }
    }

    public function getBreadcrumbs(): Breadcrumbs
    {
        return new AdmininistrationBreadcrumbs();
    }

    /**
     * @throws InvalidTrackerIdProvidedException
     */
    private function checkIdProvidedValidity(?string $submited_id): void
    {
        if (! $submited_id || $this->int_validator->validate($submited_id)) {
            return;
        }
        throw new InvalidTrackerIdProvidedException();
    }
}
