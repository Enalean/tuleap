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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveTitleValueUserCanSee;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;

final readonly class TitleValueRetriever implements RetrieveTitleValueUserCanSee, RetrieveUserStoryTitle, RetrieveFeatureTitle
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
        private RetrieveUser $retrieve_user,
        private RetrieveSemanticTitleField $title_field_retriever,
    ) {
    }

    #[\Override]
    public function getTitle(TimeboxIdentifier $timebox_identifier, UserIdentifier $user_identifier): ?string
    {
        return $this->getArtifactTitleUserCanRead(
            $this->artifact_retriever->getNonNullArtifact($timebox_identifier),
            $this->retrieve_user->getUserWithId($user_identifier),
        );
    }

    #[\Override]
    public function getUserStoryTitle(UserStoryIdentifier $user_story_identifier, UserIdentifier $user_identifier): ?string
    {
        return $this->getArtifactTitleUserCanRead(
            $this->artifact_retriever->getNonNullArtifact($user_story_identifier),
            $this->retrieve_user->getUserWithId($user_identifier),
        );
    }

    #[\Override]
    public function getFeatureTitle(FeatureIdentifier $feature_identifier, UserIdentifier $user_identifier): ?string
    {
        return $this->getArtifactTitleUserCanRead(
            $this->artifact_retriever->getNonNullArtifact($feature_identifier),
            $this->retrieve_user->getUserWithId($user_identifier),
        );
    }

    private function getArtifactTitleUserCanRead(Artifact $artifact, \PFUser $user): ?string
    {
        $title_field = $this->title_field_retriever->fromTracker($artifact->getTracker());
        if (! $title_field || ! $title_field->userCanRead($user)) {
            return null;
        }

        $last_changeset = $artifact->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        $last_changeset_value = $last_changeset->getValue($title_field);
        if (! $last_changeset_value instanceof Tracker_Artifact_ChangesetValue_Text) {
            return null;
        }

        return $last_changeset_value->getContentAsText();
    }
}
