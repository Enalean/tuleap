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
    private function __construct(private array $user_story)
    {
    }
    public static function withUserStory(array $user_story): self
    {
        return new self($user_story);
    }
    public static function withoutUserStory(): self
    {
        return new self([]);
    }
    public function searchStoriesOfMirroredIteration(MirroredIterationIdentifier $mirrored_iteration_identifier): array
    {
        return $this->user_story;
    }
}
