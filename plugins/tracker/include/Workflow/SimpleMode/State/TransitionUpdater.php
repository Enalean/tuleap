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

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollectionUpdater;
use Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdater;
use Tuleap\Tracker\Workflow\Transition\NoSiblingTransitionException;

class TransitionUpdater
{
    /**
     * @var TransitionExtractor
     */
    private $transition_extractor;

    /**
     * @var ConditionsUpdater
     */
    private $conditions_updater;

    /**
     * @var PostActionCollectionUpdater
     */
    private $action_collection_updater;

    public function __construct(
        ConditionsUpdater $conditions_updater,
        TransitionExtractor $transition_extractor,
        PostActionCollectionUpdater $action_collection_updater
    ) {
        $this->transition_extractor      = $transition_extractor;
        $this->conditions_updater        = $conditions_updater;
        $this->action_collection_updater = $action_collection_updater;
    }

    /**
     * @throws \Tuleap\Tracker\Workflow\Transition\Condition\ConditionsUpdateException
     */
    public function updateStatePreConditions(
        State $state,
        Transition $transition,
        array $authorized_user_group_ids,
        array $not_empty_field_ids,
        bool $is_comment_required
    ) {
        $this->conditions_updater->update(
            $transition,
            $authorized_user_group_ids,
            $not_empty_field_ids,
            $is_comment_required
        );

        try {
            $siblings_transitions = $this->transition_extractor->extractSiblingTransitionsFromState($state, $transition);
            foreach ($siblings_transitions as $sibling) {
                $this->conditions_updater->update(
                    $sibling,
                    $authorized_user_group_ids,
                    $not_empty_field_ids,
                    $is_comment_required
                );
            }
        } catch (NoSiblingTransitionException $exception) {
            //Do nothing, there simply are no siblings to update
        }
    }

    /**
     * @throws \DataAccessQueryException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     */
    public function updateStateActions(State $state, PostActionCollection $post_actions)
    {
        foreach ($state->getTransitions() as $transition) {
            $this->action_collection_updater->updateByTransition($transition, $post_actions);
        }
    }
}
