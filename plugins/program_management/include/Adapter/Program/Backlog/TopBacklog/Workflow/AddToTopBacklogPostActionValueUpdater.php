<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use Transition;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\CreatePostAction;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\DeletePostAction;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

final class AddToTopBacklogPostActionValueUpdater implements PostActionUpdater
{
    public function __construct(private DeletePostAction $delete_post_action, private DBTransactionExecutor $db_transaction_executor, private CreatePostAction $create_post_action)
    {
    }

    #[\Override]
    public function updateByTransition(PostActionCollection $actions, Transition $transition): void
    {
        $add_to_top_backlog_post_action_value = [];
        foreach ($actions->getExternalPostActionsValue() as $external_post_action_value) {
            if ($external_post_action_value instanceof AddToTopBacklogPostActionValue) {
                $add_to_top_backlog_post_action_value[] = $external_post_action_value;
            }
        }

        $transition_id = (int) $transition->getId();

        $this->db_transaction_executor->execute(function () use ($transition_id, $add_to_top_backlog_post_action_value) {
            $this->delete_post_action->deleteTransitionPostActions($transition_id);

            if (count($add_to_top_backlog_post_action_value) > 0) {
                $this->create_post_action->createPostActionForTransitionID($transition_id);
            }
        });
    }
}
