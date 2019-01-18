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
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuildRepository;
use Tuleap\Tracker\Workflow\Update\PostAction;

class PostActionsUpdater
{
    /**
     * @var CIBuildRepository
     */
    private $ci_build_repository;

    public function __construct(CIBuildRepository $ci_build_repository)
    {
        $this->ci_build_repository = $ci_build_repository;
    }

    /**
     * Replace all actions on a transaction by given actions set.
     * @throws DataAccessQueryException
     */
    public function updateByTransition(Transition $transition, PostActionCollection $actions): void
    {
        $this->updateCIBuild($transition, $actions);
    }

    /**
     * @throws DataAccessQueryException
     */
    private function updateCIBuild(Transition $transition, PostActionCollection $actions)
    {
        $action_ids = $this->ci_build_repository->findAllIdsByTransition($transition);

        $added_or_updated_actions    = $actions->getCIBuildActions();
        $added_or_updated_action_ids = $this->extractPostActionIds($added_or_updated_actions);
        $this->ci_build_repository->deleteAllByTransitionIfIdNotIn($transition, $added_or_updated_action_ids);

        $diff = $actions->compareCIBuildActionsTo($action_ids);
        foreach ($diff->getAddedActions() as $added_action) {
            $this->ci_build_repository->create($transition, $added_action);
        }
        foreach ($diff->getUpdatedActions() as $updated_action) {
            $this->ci_build_repository->update($updated_action);
        }
    }

    /**
     * @param PostAction[] $actions
     * @return int[]
     */
    private function extractPostActionIds(array $actions)
    {
        return array_map(
            function (PostAction $action) {
                return $action->getId();
            },
            $actions
        );
    }
}
