<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyFeatureHasAtLeastOneUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FeatureHasUserStoriesVerifier implements VerifyFeatureHasAtLeastOneUserStory
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private RetrieveUser $retrieve_user,
        private SearchChildrenOfFeature $search_children_of_feature,
    ) {
    }

    public function hasStoryLinked(FeatureIdentifier $feature, UserIdentifier $user): bool
    {
        $pfuser          = $this->retrieve_user->getUserWithId($user);
        $linked_children = $this->search_children_of_feature->getChildrenOfFeatureInTeamProjects($feature->id);
        foreach ($linked_children as $linked_child) {
            $child = $this->artifact_factory->getArtifactByIdUserCanView($pfuser, $linked_child['children_id']);
            if ($child) {
                return true;
            }
        }

        return false;
    }
}
