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

namespace Tuleap\ProgramManagement\Adapter;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisibleByProgram;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\VerifyUserStoryIsVisible;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ArtifactVisibleVerifier implements VerifyIsVisibleArtifact, VerifyFeatureIsVisibleByProgram, VerifyFeatureIsVisible, VerifyUserStoryIsVisible
{
    public function __construct(
        private \Tracker_ArtifactFactory $artifact_factory,
        private RetrieveUser $user_retriever,
    ) {
    }

    #[\Override]
    public function isVisible(int $artifact_id, UserIdentifier $user_identifier): bool
    {
        $user     = $this->user_retriever->getUserWithId($user_identifier);
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $artifact_id);
        return $artifact !== null;
    }

    #[\Override]
    public function isFeatureVisibleAndInProgram(
        int $feature_id,
        UserIdentifier $user_identifier,
        ProgramIdentifier $program,
        ?PermissionBypass $bypass,
    ): bool {
        if ($bypass) {
            $artifact = $this->artifact_factory->getArtifactById($feature_id);
            return $artifact !== null && (int) $artifact->getTracker()->getGroupId() === $program->getId();
        }
        $user     = $this->user_retriever->getUserWithId($user_identifier);
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        return $artifact !== null && (int) $artifact->getTracker()->getGroupId() === $program->getId();
    }

    #[\Override]
    public function isVisibleFeature(int $feature_id, UserIdentifier $user): bool
    {
        return $this->isVisible($feature_id, $user);
    }

    #[\Override]
    public function isUserStoryVisible(int $user_story_id, UserIdentifier $user): bool
    {
        return $this->isVisible($user_story_id, $user);
    }
}
