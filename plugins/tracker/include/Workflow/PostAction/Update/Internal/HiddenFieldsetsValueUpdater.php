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

class HiddenFieldsetsValueUpdater implements PostActionUpdater
{
    /**
     * @var HiddenFieldsetsValueRepository
     */
    private $hidden_fieldsets_value_repository;

    /**
     * @var HiddenFieldsetsValueValidator
     */
    private $hidden_fieldsets_value_validator;

    public function __construct(
        HiddenFieldsetsValueRepository $hidden_fieldsets_value_repository,
        HiddenFieldsetsValueValidator $hidden_fieldsets_value_validator
    ) {
        $this->hidden_fieldsets_value_repository = $hidden_fieldsets_value_repository;
        $this->hidden_fieldsets_value_validator  = $hidden_fieldsets_value_validator;
    }

    /**
     * @throws InvalidPostActionException
     * @throws \Tuleap\Tracker\Workflow\Transition\OrphanTransitionException
     */
    public function updateByTransition(PostActionCollection $actions, Transition $transition): void
    {
        $actions->validateHiddenFieldsetsActions(
            $this->hidden_fieldsets_value_validator,
            $transition->getWorkflow()->getTracker()
        );

        $this->hidden_fieldsets_value_repository->deleteAllByTransition($transition);

        foreach ($actions->getHiddenFieldsetsPostActions() as $action) {
            $this->hidden_fieldsets_value_repository->create($transition, $action);
        }
    }
}
