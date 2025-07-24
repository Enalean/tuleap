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

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\RetrieveUserStoryCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifier;

final class RetrieveUserStoryCrossRefStub implements RetrieveUserStoryCrossRef
{
    /**
     * @param string[] $short_names
     */
    private function __construct(private bool $always_return, private array $short_names)
    {
    }

    public static function withShortname(string $tracker_shortname): self
    {
        return new self(true, [$tracker_shortname]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveShortNames(string $tracker_shortname, string ...$other_names): self
    {
        return new self(false, [$tracker_shortname, ...$other_names]);
    }

    #[\Override]
    public function getUserStoryCrossRef(UserStoryIdentifier $user_story_identifier): string
    {
        if ($this->always_return) {
            $name = $this->short_names[0];
            return $name . ' #' . $user_story_identifier->getId();
        }
        if (count($this->short_names) > 0) {
            $name = array_shift($this->short_names);
            return $name . ' #' . $user_story_identifier->getId();
        }
        throw new \LogicException('No tracker shortname configured');
    }
}
