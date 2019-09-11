<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow;

use Luracast\Restler\RestException;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\REST\WorkflowTransitionPOSTRepresentation;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionCreator;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use UserManager;
use Workflow;
use WorkflowFactory;

class TransitionPOSTHandler
{
    /** @var UserManager */
    private $user_manager;
    /** @var \TrackerFactory */
    private $tracker_factory;
    /** @var ProjectStatusVerificator */
    private $project_status_verificator;
    /** @var TransitionsPermissionsChecker */
    private $permissions_checker;
    /** @var WorkflowFactory */
    private $workflow_factory;
    /** @var \TransitionFactory */
    private $transition_factory;
    /** @var TransitionValidator */
    private $validator;
    /** @var DBTransactionExecutor */
    private $transaction_executor;

    /**
     * @var StateFactory
     */
    private $state_factory;

    /**
     * @var TransitionCreator
     */
    private $transition_creator;

    public function __construct(
        UserManager $user_manager,
        \TrackerFactory $tracker_factory,
        ProjectStatusVerificator $project_status_verificator,
        TransitionsPermissionsChecker $permissions_checker,
        WorkflowFactory $workflow_factory,
        \TransitionFactory $transition_factory,
        TransitionValidator $validator,
        DBTransactionExecutor $transaction_executor,
        StateFactory $state_factory,
        TransitionCreator $transition_creator
    ) {
        $this->user_manager               = $user_manager;
        $this->tracker_factory            = $tracker_factory;
        $this->project_status_verificator = $project_status_verificator;
        $this->permissions_checker        = $permissions_checker;
        $this->workflow_factory           = $workflow_factory;
        $this->transition_factory         = $transition_factory;
        $this->validator                  = $validator;
        $this->transaction_executor       = $transaction_executor;
        $this->state_factory              = $state_factory;
        $this->transition_creator         = $transition_creator;
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_LoginException
     */
    public function handle(int $tracker_id, int $from_id, int $to_id): WorkflowTransitionPOSTRepresentation
    {
        $current_user = $this->user_manager->getCurrentUser();
        $tracker      = $this->getTrackerByTrackerId($tracker_id);
        $this->project_status_verificator->checkProjectStatusAllowsAllUsersToAccessIt($tracker->getProject());
        $this->permissions_checker->checkCreate($current_user, $tracker);

        $workflow = $this->getWorkflowByTrackerId($tracker_id);
        $params   = $this->validator->validateForCreation($workflow, $from_id, $to_id);
        try {
            $representation = null;
            $this->transaction_executor->execute(
                function () use ($workflow, $params, &$representation) {
                    if ($workflow->isAdvanced()) {
                        $transition = $this->transition_factory->createAndSaveTransition($workflow, $params);
                    } else {
                        $state      = $this->state_factory->getStateFromValueId($workflow, $params->getToId());
                        $transition = $this->transition_creator->createTransitionInState($state, $workflow, $params);
                    }

                    $representation = $this->buildRepresentation($transition);

                    return;
                }
            );
            return $representation;
        } catch (ConditionsUpdateException $exception) {
            throw new I18NRestException(400, dgettext('tuleap-tracker', 'The transition has not been updated.'));
        }
    }

    private function buildRepresentation(\Transition $transition): WorkflowTransitionPOSTRepresentation
    {
        $transition_representation = new WorkflowTransitionPOSTRepresentation();
        $transition_representation->build($transition);
        return $transition_representation;
    }

    /**
     * Checks if workflow exists for the tracker before return object
     *
     * @throws I18NRestException 404
     */
    private function getWorkflowByTrackerId(int $tracker_id): Workflow
    {
        $workflow = $this->workflow_factory->getWorkflowByTrackerId($tracker_id);

        if ($workflow === null) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'This tracker has no workflow.'));
        }

        return $workflow;
    }

    /**
     * @throws I18NRestException 404
     */
    private function getTrackerByTrackerId(int $tracker_id): \Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if ($tracker === null) {
            throw new I18NRestException(404, dgettext('tuleap-tracker', 'This tracker does not exist.'));
        }
        return $tracker;
    }
}
