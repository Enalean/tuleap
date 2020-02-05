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

namespace Tuleap\Tracker\Workflow\SimpleMode;

use EventManager;
use Transition;
use Tuleap\Tracker\Workflow\Event\GetWorkflowExternalPostActionsValuesForUpdate;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\NoFrozenFieldsPostActionException;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\NoHiddenFieldsetsPostActionException;
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionsMapper;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;

class TransitionReplicator
{
    /** @var \Workflow_Transition_ConditionFactory */
    private $condition_factory;
    /** @var ConditionsUpdater */
    private $conditions_updater;
    /** @var PostActionsRetriever */
    private $post_actions_retriever;
    /** @var PostActionCollectionUpdater */
    private $post_actions_updater;
    /** @var PostActionsMapper */
    private $post_action_mapper;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        \Workflow_Transition_ConditionFactory $condition_factory,
        ConditionsUpdater $conditions_updater,
        PostActionsRetriever $post_actions_retriever,
        PostActionCollectionUpdater $post_actions_updater,
        PostActionsMapper $post_action_mapper,
        EventManager $event_manager
    ) {
        $this->condition_factory      = $condition_factory;
        $this->conditions_updater     = $conditions_updater;
        $this->post_actions_retriever = $post_actions_retriever;
        $this->post_actions_updater   = $post_actions_updater;
        $this->post_action_mapper     = $post_action_mapper;
        $this->event_manager          = $event_manager;
    }

    /**
     * @throws ConditionsUpdateException
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     */
    public function replicate(Transition $from, Transition $to): void
    {
        $this->replicateConditions($from, $to);
        $this->replicatePostActions($from, $to);
    }

    /**
     * @throws ConditionsUpdateException
     */
    private function replicateConditions(Transition $from, Transition $to)
    {
        $ugroups                   = $this->condition_factory
            ->getPermissionsCondition($from)
            ->getAuthorizedUGroupsAsArray();
        $authorized_user_group_ids = [];
        foreach ($ugroups as $ugroup) {
            $authorized_user_group_ids[] = $ugroup['ugroup_id'];
        }

        $not_empty_field_ids = $this->condition_factory
            ->getFieldNotEmptyCondition($from)
            ->getFieldIds();
        $is_comment_required = $this->condition_factory
            ->getCommentNotEmptyCondition($from)
            ->isCommentRequired();
        $this->conditions_updater->update(
            $to,
            $authorized_user_group_ids,
            $not_empty_field_ids,
            $is_comment_required
        );
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     */
    private function replicatePostActions(Transition $from, Transition $to)
    {
        $post_actions = $this->getPostActionsForUpdate($from);
        $this->post_actions_updater->updateByTransition($to, $post_actions);
    }

    private function getPostActionsForUpdate(Transition $transition): PostActionCollection
    {
        $ci_builds        = $this->post_actions_retriever->getCIBuilds($transition);
        $update_ci_builds = $this->post_action_mapper->convertToCIBuildWithNullId(...$ci_builds);

        $set_date_values    = $this->post_actions_retriever->getSetDateFieldValues($transition);
        $update_date_values = $this->post_action_mapper->convertToSetDateValueWithNullId(...$set_date_values);

        $set_float_values    = $this->post_actions_retriever->getSetFloatFieldValues($transition);
        $update_float_values = $this->post_action_mapper->convertToSetFloatValueWithNullId(...$set_float_values);

        $set_int_values    = $this->post_actions_retriever->getSetIntFieldValues($transition);
        $update_int_values = $this->post_action_mapper->convertToSetIntValueWithNullId(...$set_int_values);

        try {
            $frozen_fields_action = $this->post_actions_retriever->getFrozenFields($transition);
            $frozen_fields_value  = $this->post_action_mapper->convertToFrozenFieldValueWithNullId($frozen_fields_action);
        } catch (NoFrozenFieldsPostActionException $exception) {
            $frozen_fields_value = [];
        }

        try {
            $hidden_fieldsets_action = $this->post_actions_retriever->getHiddenFieldsets($transition);
            $hidden_fieldsets_value  = $this->post_action_mapper->convertToHiddenFieldsetsValueWithNullId($hidden_fieldsets_action);
        } catch (NoHiddenFieldsetsPostActionException $exception) {
            $hidden_fieldsets_value = [];
        }

        $event = new GetWorkflowExternalPostActionsValuesForUpdate($transition);
        $this->event_manager->processEvent($event);
        $external_post_actions_value = $event->getExternalValues();

        return new PostActionCollection(
            ...array_merge(
                $update_ci_builds,
                $update_date_values,
                $update_float_values,
                $update_int_values,
                $frozen_fields_value,
                $hidden_fieldsets_value,
                $external_post_actions_value
            )
        );
    }
}
