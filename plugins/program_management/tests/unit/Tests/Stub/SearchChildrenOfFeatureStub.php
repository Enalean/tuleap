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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;

final class SearchChildrenOfFeatureStub implements SearchChildrenOfFeature
{
    /**
     * @param array<int[]> $successive_returns
     */
    private function __construct(private bool $always_return, private array $successive_returns)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withUserStoryIds(int $user_story_id, int ...$other_ids): self
    {
        return new self(true, [[$user_story_id, ...$other_ids]]);
    }

    /**
     * @param array<int[]> $user_story_ids
     */
    public static function withSuccessiveIds(array $user_story_ids): self
    {
        return new self(false, $user_story_ids);
    }

    public static function withoutUserStories(): self
    {
        return new self(true, [[]]);
    }

    #[\Override]
    public function getChildrenOfFeatureInTeamProjects(FeatureIdentifier $feature): array
    {
        if ($this->always_return) {
            return $this->successive_returns[0];
        }
        if (count($this->successive_returns) > 0) {
            return array_shift($this->successive_returns);
        }
        return [];
    }
}
