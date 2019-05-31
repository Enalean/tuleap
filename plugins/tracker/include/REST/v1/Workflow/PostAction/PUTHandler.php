<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use PFUser;
use Transition;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\PostActionCollectionJsonParser;
use Tuleap\Tracker\REST\v1\Workflow\PostActionsPUTRepresentation;
use Tuleap\Tracker\REST\v1\Workflow\TransitionsPermissionsChecker;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\IncompatibleWorkflowModeException;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionUpdater;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;

class PUTHandler
{
    /**
     * @var TransitionsPermissionsChecker
     */
    private $transitions_permissions_checker;

    /**
     * @var ProjectStatusVerificator
     */
    private $project_status_verificator;

    /**
     * @var PostActionCollectionJsonParser
     */
    private $post_action_collection_json_parser;
    /**
     * @var PostActionCollectionUpdater
     */
    private $action_collection_updater;

    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;

    /**
     * @var StateFactory
     */
    private $state_factory;

    /**
     * @var TransitionUpdater
     */
    private $transition_updater;

    public function __construct(
        TransitionsPermissionsChecker $transitions_permissions_checker,
        ProjectStatusVerificator $project_status_verificator,
        PostActionCollectionJsonParser $post_action_collection_json_parser,
        PostActionCollectionUpdater $action_collection_updater,
        TrackerChecker $tracker_checker,
        DBTransactionExecutor $transaction_executor,
        StateFactory $state_factory,
        TransitionUpdater $transition_updater
    ) {
        $this->transitions_permissions_checker    = $transitions_permissions_checker;
        $this->project_status_verificator         = $project_status_verificator;
        $this->post_action_collection_json_parser = $post_action_collection_json_parser;
        $this->action_collection_updater          = $action_collection_updater;
        $this->transaction_executor               = $transaction_executor;
        $this->tracker_checker                    = $tracker_checker;
        $this->state_factory                      = $state_factory;
        $this->transition_updater                 = $transition_updater;
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Luracast\Restler\RestException
     * @throws \Tuleap\REST\I18NRestException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     * @throws \Tuleap\Tracker\Workflow\Transition\OrphanTransitionException
     * @throws IncompatibleWorkflowModeException
     * @throws \Tuleap\Tracker\REST\v1\Workflow\PostAction\PostActionNonEligibleForTrackerException
     */
    public function handle(
        PFUser $current_user,
        Transition $transition,
        PostActionsPUTRepresentation $post_actions_representation
    ) {
        $this->transitions_permissions_checker->checkRead($current_user, $transition);
        $workflow = $transition->getWorkflow();
        $project  = $workflow->getTracker()->getProject();
        $this->project_status_verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $post_actions = $this->post_action_collection_json_parser->parse($workflow, $post_actions_representation->post_actions);

        $this->tracker_checker->checkPostActionsAreEligibleForTracker($workflow->getTracker(), $post_actions);

        $this->transaction_executor->execute(
            function () use ($workflow, $transition, $post_actions) {
                if ($workflow->isAdvanced()) {
                    $this->action_collection_updater->updateByTransition($transition, $post_actions);
                } else {
                    $state = $this->state_factory->getStateFromValueId($workflow, (int) $transition->getIdTo());
                    $this->transition_updater->updateStateActions($state, $post_actions);
                }
            }
        );
    }
}
