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

namespace Tuleap\Tracker\Workflow\PostAction;

use Tuleap\Tracker\Workflow\PostAction\ReadOnly\NoReadOnlyFieldsPostActionException;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyFields;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyFieldsFactory;

class PostActionsRetriever
{
    /** @var \Transition_PostAction_CIBuildFactory */
    private $cibuild_factory;
    /** @var \Transition_PostAction_FieldFactory */
    private $field_factory;
    /** @var ReadOnlyFieldsFactory */
    private $read_only_fields_factory;

    public function __construct(
        \Transition_PostAction_CIBuildFactory $cibuild_factory,
        \Transition_PostAction_FieldFactory $field_factory,
        ReadOnlyFieldsFactory $read_only_fields_factory
    ) {
        $this->cibuild_factory          = $cibuild_factory;
        $this->field_factory            = $field_factory;
        $this->read_only_fields_factory = $read_only_fields_factory;
    }

    /**
     * @return \Transition_PostAction_CIBuild[]
     */
    public function getCIBuilds(\Transition $transition): array
    {
        return $this->cibuild_factory->loadPostActions($transition);
    }

    /**
     * @return \Transition_PostAction_Field_Date[]
     */
    public function getSetDateFieldValues(\Transition $transition): array
    {
        return $this->field_factory->getSetDateFieldValues($transition);
    }

    /**
     * @return \Transition_PostAction_Field_Float[]
     */
    public function getSetFloatFieldValues(\Transition $transition): array
    {
        return $this->field_factory->getSetFloatFieldValues($transition);
    }

    /**
     * @return \Transition_PostAction_Field_Int[]
     */
    public function getSetIntFieldValues(\Transition $transition): array
    {
        return $this->field_factory->getSetIntFieldValues($transition);
    }

    /**
     * @throws NoReadOnlyFieldsPostActionException
     */
    public function getReadOnlyFields(\Transition $transition): ReadOnlyFields
    {
        $post_actions = $this->read_only_fields_factory->loadPostActions($transition);
        if (empty($post_actions)) {
            throw new NoReadOnlyFieldsPostActionException();
        }
        // There is only one ReadOnlyFields post-action per transition
        return $post_actions[0];
    }
}
