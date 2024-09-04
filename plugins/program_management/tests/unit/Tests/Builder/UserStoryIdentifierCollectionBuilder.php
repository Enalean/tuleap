<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\UserStory\UserStoryIdentifierCollection;
use Tuleap\ProgramManagement\Tests\Stub\SearchChildrenOfFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserStoryIsVisibleStub;

final class UserStoryIdentifierCollectionBuilder
{
    /**
     * @no-named-arguments
     */
    public static function buildWithIds(int $user_story_id, int ...$other_ids): UserStoryIdentifierCollection
    {
        return UserStoryIdentifierCollection::fromFeatureCollection(
            SearchChildrenOfFeatureStub::withUserStoryIds($user_story_id, ...$other_ids),
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
            FeatureIdentifierCollectionBuilder::buildWithIds(595),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public static function buildEmpty(): UserStoryIdentifierCollection
    {
        return UserStoryIdentifierCollection::fromFeatureCollection(
            SearchChildrenOfFeatureStub::withoutUserStories(),
            VerifyUserStoryIsVisibleStub::withAlwaysVisibleUserStories(),
            FeatureIdentifierCollectionBuilder::buildWithIds(576),
            UserIdentifierStub::buildGenericUser()
        );
    }
}
