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

use Tuleap\DB\TransactionExecutor;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use Tuleap\Tracker\Workflow\Transition\OrphanTransitionException;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionRetriever;

class TransitionPatcher
{
    /** @var ConditionsUpdater */
    private $conditions_updater;
    /** @var TransitionRetriever */
    private $transition_retriever;
    /** @var TransactionExecutor */
    private $transaction_executor;

    public function __construct(
        ConditionsUpdater $transition_updater,
        TransitionRetriever $transition_retriever,
        TransactionExecutor $transaction_executor
    ) {
        $this->conditions_updater   = $transition_updater;
        $this->transition_retriever = $transition_retriever;
        $this->transaction_executor = $transaction_executor;
    }

    /**
     * @throws OrphanTransitionException
     * @throws I18NRestException
     * @throws ConditionsUpdateException
     */
    public function patch(\Transition $transition, WorkflowTransitionPATCHRepresentation $transition_conditions): void
    {
        $authorized_user_group_ids = $transition_conditions->getAuthorizedUserGroupIds();
        if (count($authorized_user_group_ids) === 0) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-tracker', 'There must be at least one authorized user group.')
            );
        }

        $this->transaction_executor->execute(
            function () use ($transition, $authorized_user_group_ids, $transition_conditions) {
                $workflow = $transition->getWorkflow();
                $this->conditions_updater->update(
                    $transition,
                    $authorized_user_group_ids,
                    $transition_conditions->not_empty_field_ids,
                    $transition_conditions->is_comment_required
                );
                if ($workflow->isAdvanced()) {
                    return;
                }
                try {
                    $siblings_collection = $this->transition_retriever->getSiblingTransitions($transition);
                    foreach ($siblings_collection->getTransitions() as $sibling) {
                        $this->conditions_updater->update(
                            $sibling,
                            $authorized_user_group_ids,
                            $transition_conditions->not_empty_field_ids,
                            $transition_conditions->is_comment_required
                        );
                    }
                } catch (NoSiblingTransitionException $e) {
                    //Do nothing, there simply are no siblings to update
                }
            }
        );
    }
}
