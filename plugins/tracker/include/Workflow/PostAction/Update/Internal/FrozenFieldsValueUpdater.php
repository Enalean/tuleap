<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

class FrozenFieldsValueUpdater implements PostActionUpdater
{
    /**
     * @var FrozenFieldsValueRepository
     */
    private $frozen_fields_repository;

    /**
     * @var FrozenFieldsValueValidator
     */
    private $frozen_fields_validator;

    public function __construct(
        FrozenFieldsValueRepository $frozen_fields_repository,
        FrozenFieldsValueValidator $frozen_fields_validator
    ) {
        $this->frozen_fields_repository = $frozen_fields_repository;
        $this->frozen_fields_validator = $frozen_fields_validator;
    }

    /**
     * @throws InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\Transition\OrphanTransitionException
     */
    public function updateByTransition(PostActionCollection $actions, Transition $transition): void
    {
        $actions->validateFrozenFieldsActions(
            $this->frozen_fields_validator,
            $transition->getWorkflow()->getTracker()
        );

        $this->frozen_fields_repository->deleteAllByTransition($transition);

        foreach ($actions->getFrozenFieldsPostActions() as $action) {
            $this->frozen_fields_repository->create($transition, $action);
        }
    }
}
