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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\UserStory;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerIdentifierProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerId;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class TrackerIdRetriever implements RetrieveTrackerId
{
    public function __construct(private RetrieveFullArtifact $artifact_retriever)
    {
    }

    public function getTracker(UserStoryIdentifier $user_story_identifier): TrackerIdentifier
    {
        $artifact = $this->artifact_retriever->getNonNullArtifact($user_story_identifier);
        return TrackerIdentifierProxy::fromTracker($artifact->getTracker());
    }
}
