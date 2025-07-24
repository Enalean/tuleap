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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureChange;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldDataTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID          = 114;
    private const ARTIFACT_LINK_FIELD_ID = 717;
    private const FIRST_USER_STORY_ID    = 368;
    private const SECOND_USER_STORY_ID   = 139;
    private const THIRD_USER_STORY_ID    = 795;
    private const FOURTH_USER_STORY_ID   = 935;
    private array $user_stories_to_add;
    private array $user_stories_to_remove;

    #[\Override]
    protected function setUp(): void
    {
        $first_feature_change         = FeatureChange::fromRaw([
            'id'         => self::FIRST_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $second_feature_change        = FeatureChange::fromRaw([
            'id'         => self::SECOND_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $third_feature_change         = FeatureChange::fromRaw([
            'id'         => self::THIRD_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $fourth_feature_change        = FeatureChange::fromRaw([
            'id'         => self::FOURTH_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $this->user_stories_to_add    = [$first_feature_change, $second_feature_change];
        $this->user_stories_to_remove = [$third_feature_change, $fourth_feature_change];
    }

    private function callMethodUnderTest(): array
    {
        return (new FieldData(
            $this->user_stories_to_add,
            $this->user_stories_to_remove,
            self::ARTIFACT_LINK_FIELD_ID
        ))->getFieldDataForChangesetCreationFormat(self::FIRST_TEAM_ID);
    }

    public function testItFormatsAddedAndRemovedUserStoriesForTrackerPlugin(): void
    {
        self::assertSame([self::ARTIFACT_LINK_FIELD_ID => [
            'new_values'     => self::FIRST_USER_STORY_ID . ',' . self::SECOND_USER_STORY_ID,
            'removed_values' => [
                self::THIRD_USER_STORY_ID  => self::THIRD_USER_STORY_ID,
                self::FOURTH_USER_STORY_ID => self::FOURTH_USER_STORY_ID,
            ],
        ],
        ], $this->callMethodUnderTest());
    }

    public function testUserStoriesToAddFromAnotherProjectAreSkipped(): void
    {
        $first_feature_change      = FeatureChange::fromRaw([
            'id'         => self::FIRST_USER_STORY_ID,
            'project_id' => 140,
        ]);
        $second_feature_change     = FeatureChange::fromRaw([
            'id'         => self::SECOND_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $this->user_stories_to_add = [$first_feature_change, $second_feature_change];

        self::assertSame([self::ARTIFACT_LINK_FIELD_ID => [
            'new_values'     => (string) self::SECOND_USER_STORY_ID,
            'removed_values' => [
                self::THIRD_USER_STORY_ID  => self::THIRD_USER_STORY_ID,
                self::FOURTH_USER_STORY_ID => self::FOURTH_USER_STORY_ID,
            ],
        ],
        ], $this->callMethodUnderTest());
    }

    public function testUserStoriesAddedAreNotAlsoRemoved(): void
    {
        $first_feature_change         = FeatureChange::fromRaw([
            'id'         => self::FIRST_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $third_feature_change         = FeatureChange::fromRaw([
            'id'         => self::THIRD_USER_STORY_ID,
            'project_id' => self::FIRST_TEAM_ID,
        ]);
        $this->user_stories_to_remove = [$first_feature_change, $third_feature_change];

        self::assertSame([self::ARTIFACT_LINK_FIELD_ID => [
            'new_values'     => self::FIRST_USER_STORY_ID . ',' . self::SECOND_USER_STORY_ID,
            'removed_values' => [
                self::THIRD_USER_STORY_ID => self::THIRD_USER_STORY_ID,
            ],
        ],
        ], $this->callMethodUnderTest());
    }

    public function testItFormatsEmptyArraysForTrackerPlugin(): void
    {
        $this->user_stories_to_add    = [];
        $this->user_stories_to_remove = [];

        self::assertSame([self::ARTIFACT_LINK_FIELD_ID => [
            'new_values'     => '',
            'removed_values' => [],
        ],
        ], $this->callMethodUnderTest());
    }
}
