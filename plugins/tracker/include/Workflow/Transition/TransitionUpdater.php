<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Workflow\Transition;

use Exception;
use TransitionFactory;
use Tuleap\DB\TransactionExecutor;
use Workflow_Transition_ConditionFactory;

/**
 * Useful class to update a workflow transition in a single transaction.
 */
class TransitionUpdater
{

    /**
     * @var TransitionFactory
     */
    private $transition_factory;

    /**
     * @var TransactionExecutor
     */
    private $transaction_executor;

    public function __construct(TransitionFactory $transition_factory, TransactionExecutor $transaction_executor)
    {
        $this->transition_factory   = $transition_factory;
        $this->transaction_executor = $transaction_executor;
    }

    /**
     * @throws TransitionUpdateException
     */
    public function update(
        \Transition $transition,
        array $authorized_user_group_ids,
        array $not_empty_field_ids,
        $is_comment_required
    ) {
        try {
            $this->transaction_executor->execute(
                function () use ($transition, $not_empty_field_ids, $is_comment_required, $authorized_user_group_ids) {
                    $condition_factory = Workflow_Transition_ConditionFactory::build();
                    $condition_factory->addCondition(
                        $transition,
                        $not_empty_field_ids,
                        $is_comment_required
                    );
                    $this->updatePermissions($transition, $authorized_user_group_ids);
                }
            );
        } catch (Exception $exception) {
            throw new TransitionUpdateException(
                sprintf(
                    dgettext('tuleap-tracker', "Cannot update transition with id '%d'"),
                    $transition->getId()
                ),
                0,
                $exception
            );
        }
    }

    /**
     * @throws OrphanTransitionException
     * @throws TransitionUpdateException
     */
    private function updatePermissions(\Transition $transition, array $authorized_user_group_ids)
    {
        if (count($authorized_user_group_ids) === 0) {
            throw new TransitionUpdateException(
                sprintf(
                    dgettext('tuleap-tracker', "Cannot update permissions of transition with id '%d'"),
                    $transition->getId()
                )
            );
        }

        $clear_permissions_success = permission_clear_all(
            $transition->getWorkflow()->getTracker()->group_id,
            'PLUGIN_TRACKER_WORKFLOW_TRANSITION',
            $transition->getId(),
            false
        );
        if (!$clear_permissions_success) {
            throw new TransitionUpdateException(
                sprintf(
                    dgettext('tuleap-tracker', "Cannot update permissions of transition with id '%d'"),
                    $transition->getId()
                )
            );
        }

        $add_permissions_success = $this->transition_factory->addPermissions(
            $authorized_user_group_ids,
            $transition->getId()
        );

        if (!$add_permissions_success) {
            throw new TransitionUpdateException(
                sprintf(
                    dgettext('tuleap-tracker', "Cannot update permissions of transition with id '%d'"),
                    $transition->getId()
                )
            );
        }
    }
}
