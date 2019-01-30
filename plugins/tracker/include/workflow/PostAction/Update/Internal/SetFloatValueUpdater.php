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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use DataAccessQueryException;
use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class SetFloatValueUpdater implements PostActionUpdater
{
    /**
     * @var SetFloatValueRepository
     */
    private $repository;

    public function __construct(SetFloatValueRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update (and replace) all set float value post actions with those included in given collection.
     * @throws DataAccessQueryException
     */
    public function updateByTransition(PostActionCollection $actions, Transition $transition): void
    {
        $existing_ids_collection = $this->repository->findAllIdsByTransition($transition);
        $diff                    = $actions->compareSetFloatValueActionsTo($existing_ids_collection);

        $this->repository->deleteAllByTransitionIfNotIn($transition, $diff->getUpdatedActions());

        foreach ($diff->getAddedActions() as $added_action) {
            $this->repository->create($transition, $added_action);
        }
        foreach ($diff->getUpdatedActions() as $updated_action) {
            $this->repository->update($updated_action);
        }
    }
}
