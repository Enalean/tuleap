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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveTrackerFromUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveTrackerFromUserStoryStub implements RetrieveTrackerFromUserStory
{
    private function __construct(private TrackerIdentifier $tracker_id)
    {
    }
    public static function withDefault(): self
    {
        return new self(TrackerIdentifierStub::buildWithDefault());
    }

    public static function withId(int $id): self
    {
        return new self(TrackerIdentifierStub::withId($id));
    }

    public function getUserStoryTracker(UserStoryIdentifier $user_story_identifier): TrackerIdentifier
    {
        return $this->tracker_id;
    }
}
