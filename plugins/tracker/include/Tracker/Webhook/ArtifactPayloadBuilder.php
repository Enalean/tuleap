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

use Tuleap\Project\ProjectUserUGroupMembershipsRetriever;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentationBuilder;
use Tuleap\Tracker\REST\v1\BuildCompleteTrackerRESTRepresentation;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\TuleapFunctionsUser;
use Tuleap\User\REST\MinimalUserRepresentation;

readonly class ArtifactPayloadBuilder
{
    public function __construct(
        private ChangesetRepresentationBuilder $changeset_representation_builder,
        private BuildCompleteTrackerRESTRepresentation $tracker_representation_builder,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
        private ProjectUserUGroupMembershipsRetriever $project_user_group_memberships_retriever,
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

        $user_representation = MinimalUserRepresentation::build($user, $this->provide_user_avatar_url);

        return new ArtifactPayload(
            [
                'id'                       => $artifact_id,
                'action'                   => $previous_changeset === null ? 'create' : 'update',
                'user'                     => $user_representation,
                'submitter_user_groups'    => $this->buildSubmitterUserGroups($user, $last_changeset->getTracker()->getProject()),
                'current'                  => $last_changeset_content,
                'previous'                 => $previous_changeset_content,
                'is_custom_code_execution' => $user->getId() === TuleapFunctionsUser::ID,
                'tracker'                  => $this->tracker_representation_builder->getTrackerRepresentationInTrackerContext(
                    $user,
                    $last_changeset->getTracker()
                ),
            ]
        );
    }

    /**
     * @return list<MinimalUserGroupRepresentation>
     */
    private function buildSubmitterUserGroups(\PFUser $user, \Project $project): array
    {
        $ugroups         = $this->project_user_group_memberships_retriever->getMembershipsInAProject($project, $user);
        $project_id      = (int) $project->getID();
        $representations = [];
        foreach ($ugroups as $ugroup) {
            $representations[] = new MinimalUserGroupRepresentation($project_id, $ugroup);
        }

        return $representations;
    }
}
