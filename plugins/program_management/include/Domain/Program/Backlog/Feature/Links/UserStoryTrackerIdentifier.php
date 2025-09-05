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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links;

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

/**
 * I am the ID (identifier) of a User Story Tracker.
 * @psalm-immutable
 */
final class UserStoryTrackerIdentifier implements TrackerIdentifier
{
    private function __construct(private TrackerIdentifier $tracker_identifier)
    {
    }

    #[\Override]
    public function getId(): int
    {
        return $this->tracker_identifier->getId();
    }

    public static function fromUserStory(RetrieveTrackerFromUserStory $tracker_retriever, UserStoryIdentifier $user_story): self
    {
        return new self($tracker_retriever->getUserStoryTracker($user_story));
    }
}
