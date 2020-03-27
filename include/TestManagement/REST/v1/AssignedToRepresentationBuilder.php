<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tuleap\User\REST\UserRepresentation;
use Tracker_Artifact;
use PFUser;
use Tracker_FormElementFactory;
use UserManager;

class AssignedToRepresentationBuilder
{

    /**
     * @var UserManager
     */
    private $user_manager;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    public function __construct(
        Tracker_FormElementFactory $tracker_form_element_factory,
        UserManager $user_manager
    ) {
        $this->tracker_form_element_factory = $tracker_form_element_factory;
        $this->user_manager                 = $user_manager;
    }

    /**
     * @return \Tuleap\User\REST\UserRepresentation|null
     */
    public function getAssignedToRepresentationForExecution(PFUser $user, Tracker_Artifact $execution)
    {
        $field_value  = $this->getExecutionAssignedTo($user, $execution);
        $user_id      = array_pop($field_value);
        if (! $user_id) {
            return null;
        }

        $user = $this->user_manager->getUserById($user_id);
        if (! $user) {
            return null;
        }

        $user_representation = new UserRepresentation();
        $user_representation->build($user);

        return $user_representation;
    }

    /**
     * @return array|mixed
     */
    private function getExecutionAssignedTo(PFUser $user, Tracker_Artifact $execution)
    {
        $assigned_to_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser(
            $execution->getTrackerId(),
            ExecutionRepresentation::FIELD_ASSIGNED_TO,
            $user
        );

        if ($assigned_to_field === null) {
            return [];
        }

        $changeset_value = $execution->getValue($assigned_to_field);
        if (! $changeset_value) {
            return array();
        }

        return $changeset_value->getValue();
    }
}
