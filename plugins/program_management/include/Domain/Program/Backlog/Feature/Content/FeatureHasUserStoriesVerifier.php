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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyUserStoryIsVisible;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FeatureHasUserStoriesVerifier
{
    public function __construct(
        private SearchChildrenOfFeature $search_children_of_feature,
        private VerifyUserStoryIsVisible $visibility_verifier,
    ) {
    }

    public function hasStoryLinked(FeatureIdentifier $feature, UserIdentifier $user): bool
    {
        $user_story_ids = $this->search_children_of_feature->getChildrenOfFeatureInTeamProjects($feature);
        foreach ($user_story_ids as $user_story_id) {
            if ($this->visibility_verifier->isUserStoryVisible($user_story_id, $user)) {
                return true;
            }
        }
        return false;
    }
}
