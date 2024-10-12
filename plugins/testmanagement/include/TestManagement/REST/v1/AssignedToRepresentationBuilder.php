<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use PFUser;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\REST\UserRepresentation;
use Tuleap\User\RetrieveUserById;

class AssignedToRepresentationBuilder
{
    public function __construct(
        private readonly Tracker_FormElementFactory $tracker_form_element_factory,
        private readonly RetrieveUserById $user_manager,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    /**
     * @return \Tuleap\User\REST\UserRepresentation|null
     */
    public function getAssignedToRepresentationForExecution(PFUser $user, Artifact $execution)
    {
        $field_value = $this->getExecutionAssignedTo($user, $execution);
        $user_id     = array_pop($field_value);
        if (! $user_id) {
            return null;
        }

        $user = $this->user_manager->getUserById($user_id);
        if (! $user) {
            return null;
        }

        return UserRepresentation::build($user, $this->provide_user_avatar_url);
    }

    /**
     * @return array|mixed
     */
    private function getExecutionAssignedTo(PFUser $user, Artifact $execution)
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
            return [];
        }

        return $changeset_value->getValue();
    }
}
