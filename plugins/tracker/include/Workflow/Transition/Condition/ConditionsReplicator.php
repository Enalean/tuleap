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

namespace Tuleap\Tracker\Workflow\Transition\Condition;

use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;
use Tuleap\Tracker\Workflow\Transition\Update\TransitionRetriever;

class ConditionsReplicator
{

    /** @var TransitionRetriever */
    private $transition_retriever;
    /** @var \Workflow_Transition_ConditionFactory */
    private $condition_factory;
    /** @var ConditionsUpdater */
    private $conditions_updater;

    public function __construct(
        TransitionRetriever $transition_retriever,
        \Workflow_Transition_ConditionFactory $condition_factory,
        ConditionsUpdater $conditions_updater
    ) {
        $this->transition_retriever = $transition_retriever;
        $this->condition_factory    = $condition_factory;
        $this->conditions_updater   = $conditions_updater;
    }

    /**
     * @throws ConditionsUpdateException
     */
    public function replicateFromFirstSiblingTransition(\Transition $transition): void
    {
        try {
            $sibling_transition = $this->transition_retriever->getFirstSiblingTransition($transition);
            $ugroups = $this->condition_factory
                ->getPermissionsCondition($sibling_transition)
                ->getAuthorizedUGroupsAsArray();
            $authorized_user_group_ids = [];
            foreach ($ugroups as $ugroup) {
                $authorized_user_group_ids[] = $ugroup['ugroup_id'];
            }

            $not_empty_field_ids = $this->condition_factory
                ->getFieldNotEmptyCondition($sibling_transition)
                ->getFieldIds();
            $is_comment_required = $this->condition_factory
                ->getCommentNotEmptyCondition($sibling_transition)
                ->isCommentRequired();
            $this->conditions_updater->update(
                $transition,
                $authorized_user_group_ids,
                $not_empty_field_ids,
                $is_comment_required
            );
        } catch (NoSiblingTransitionException $e) {
            //Nothing to replicate, ignore
        }
    }
}
