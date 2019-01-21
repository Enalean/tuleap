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
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use DataAccessQueryException;
use Transition;
use Tuleap\DB\TransactionExecutor;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\DuplicateCIBuildPostAction;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\InvalidCIBuildPostActionException;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionUpdater;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException;

class PostActionCollectionUpdater
{
    /**
     * @var TransactionExecutor
     */
    private $transaction_executor;

    /**
     * @var PostActionUpdater[]
     */
    private $post_action_updaters;

    public function __construct(
        TransactionExecutor $transaction_executor,
        PostActionUpdater...$post_action_updaters
    ) {
        $this->transaction_executor = $transaction_executor;
        $this->post_action_updaters = $post_action_updaters;
    }

    /**
     * Replace all actions on a transaction by given actions collection.
     * @throws DataAccessQueryException
     * @throws DuplicateCIBuildPostAction
     * @throws UnknownPostActionIdsException
     * @throws InvalidCIBuildPostActionException
     */
    public function updateByTransition(Transition $transition, PostActionCollection $actions): void
    {
        $this->transaction_executor->execute(
            function () use ($transition, $actions) {
                foreach ($this->post_action_updaters as $updater) {
                    $updater->updateByTransition($actions, $transition);
                }
            }
        );
    }
}
