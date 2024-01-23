<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook;

use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\v1\BuildCompleteTrackerRESTRepresentation;
use Tuleap\User\CCEUser;
use Tuleap\User\REST\MinimalUserRepresentation;

class ArtifactPayloadBuilder
{
    public function __construct(
        private readonly ChangesetRepresentationBuilder $changeset_representation_builder,
        private readonly BuildCompleteTrackerRESTRepresentation $tracker_representation_builder,
    ) {
    }

    public function buildPayload(\Tracker_Artifact_Changeset $last_changeset): ArtifactPayload
    {
        $user               = $last_changeset->getSubmitter();
        $previous_changeset = $last_changeset->getArtifact()->getPreviousChangeset((int) $last_changeset->getId());

        $artifact_id = $last_changeset->getArtifact()->getId();

        $last_changeset_content     = $this->changeset_representation_builder->buildWithFieldValuesWithoutPermissions(
            $last_changeset,
            $user
        );
        $previous_changeset_content = null;
        if ($previous_changeset !== null) {
            $previous_changeset_content = $this->changeset_representation_builder->buildWithFieldValuesWithoutPermissions(
                $previous_changeset,
                $user
            );
        }

        $user_representation = MinimalUserRepresentation::build($user);

        return new ArtifactPayload(
            [
                'id'                       => $artifact_id,
                'action'                   => $previous_changeset === null ? 'create' : 'update',
                'user'                     => $user_representation,
                'current'                  => $last_changeset_content,
                'previous'                 => $previous_changeset_content,
                'is_custom_code_execution' => $user->getId() === CCEUser::ID,
                'tracker'                  => $this->tracker_representation_builder->getTrackerRepresentationInTrackerContext(
                    $user,
                    $last_changeset->getTracker()
                ),
            ]
        );
    }
}
