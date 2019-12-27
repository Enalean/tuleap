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

namespace Tuleap\Tracker\REST\v1\Workflow;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\Transition\OrphanTransitionException;

class TransitionPatcher
{
    /** @var ConditionsUpdater */
    private $conditions_updater;
    /** @var DBTransactionExecutor */
    private $transaction_executor;

    /**
     * @var StateFactory
     */
    private $state_factory;

    /**
     * @var TransitionUpdater
     */
    private $transition_updater;

    public function __construct(
        ConditionsUpdater $conditions_updater,
        DBTransactionExecutor $transaction_executor,
        StateFactory $state_factory,
        TransitionUpdater $transition_updater
    ) {
        $this->conditions_updater   = $conditions_updater;
        $this->transaction_executor = $transaction_executor;
        $this->state_factory        = $state_factory;
        $this->transition_updater   = $transition_updater;
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
                if ($workflow->isAdvanced()) {
                    $this->conditions_updater->update(
                        $transition,
                        $authorized_user_group_ids,
                        $transition_conditions->not_empty_field_ids,
                        $transition_conditions->is_comment_required
                    );
                } else {
                    $state = $this->state_factory->getStateFromValueId($workflow, (int) $transition->getIdTo());
                    $this->transition_updater->updateStatePreConditions(
                        $state,
                        $authorized_user_group_ids,
                        $transition_conditions->not_empty_field_ids,
                        $transition_conditions->is_comment_required
                    );
                }
            }
        );
    }
}
