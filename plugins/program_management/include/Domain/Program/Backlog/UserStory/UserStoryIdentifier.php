<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlannableFeatureIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class UserStoryIdentifier implements ArtifactIdentifier
{
    private function __construct(private int $identifier)
    {
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromFeature(
        SearchChildrenOfFeature $user_stories_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        PlannableFeatureIdentifier $feature,
        UserIdentifier $user_identifier
    ): array {
        $rows         = $user_stories_searcher->getChildrenOfFeatureInTeamProjects($feature->getId());
        $user_stories = [];
        foreach ($rows as $row) {
            $id = $row['children_id'];
            if ($visibility_verifier->isVisible($id, $user_identifier)) {
                $user_stories[] = new self($id);
            }
        }
        return $user_stories;
    }

    public function getId(): int
    {
        return $this->identifier;
    }
}
