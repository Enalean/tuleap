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

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveUri;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;

final class URIRetriever implements RetrieveUri, RetrieveUserStoryURI, RetrieveFeatureURI
{
    public function __construct(private RetrieveFullArtifact $artifact_retriever)
    {
    }

    #[\Override]
    public function getUri(TimeboxIdentifier $timebox_identifier): string
    {
        $artifact = $this->artifact_retriever->getNonNullArtifact($timebox_identifier);
        return $artifact->getUri();
    }

    #[\Override]
    public function getUserStoryURI(UserStoryIdentifier $user_story_identifier): string
    {
        $artifact = $this->artifact_retriever->getNonNullArtifact($user_story_identifier);
        return $artifact->getUri();
    }

    #[\Override]
    public function getFeatureURI(FeatureIdentifier $feature_identifier): string
    {
        return $this->artifact_retriever->getNonNullArtifact($feature_identifier)->getUri();
    }
}
