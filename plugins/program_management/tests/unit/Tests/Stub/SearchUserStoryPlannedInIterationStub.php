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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\SearchUserStoryPlannedInIteration;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifier;

final class SearchUserStoryPlannedInIterationStub implements SearchUserStoryPlannedInIteration
{
    private function __construct(private array $user_stories)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withUserStoryIds(int $user_story_id, int ...$other_ids): self
    {
        return new self([[$user_story_id, ...$other_ids]]);
    }

    /**
     * @param array<int[]> $user_story_ids
     */
    public static function withSuccessiveIds(array $user_story_ids): self
    {
        return new self($user_story_ids);
    }

    public static function withoutUserStory(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchStoriesOfMirroredIteration(MirroredIterationIdentifier $mirrored_iteration_identifier): array
    {
        if (count($this->user_stories) > 0) {
            return array_shift($this->user_stories);
        }
        return [];
    }
}
