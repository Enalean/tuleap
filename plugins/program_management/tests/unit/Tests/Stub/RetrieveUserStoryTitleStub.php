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

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveUserStoryTitleStub implements RetrieveUserStoryTitle
{
    private function __construct(private array $titles)
    {
    }

    public static function withValue(string $value): self
    {
        return new self([$value]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveValues(string $first_title, string ...$other_titles): self
    {
        return new self([$first_title, ...$other_titles]);
    }

    public function getUserStoryTitle(UserStoryIdentifier $user_story_identifier, UserIdentifier $user_identifier): ?string
    {
        if (count($this->titles) > 0) {
            return array_shift($this->titles);
        }
        return null;
    }
}
