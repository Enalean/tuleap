<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
final class FieldData
{
    /**
     * @var FeatureChange[]
     */
    private $user_stories_to_add;
    /**
     * @var FeatureChange[]
     */
    private $user_stories_to_remove;
    /**
     * @var int
     */
    private $artifact_link_field;

    /**
     * @param FeatureChange[] $user_stories_to_add
     * @param FeatureChange[] $user_stories_to_remove
     */
    public function __construct(array $user_stories_to_add, array $user_stories_to_remove, int $artifact_link_field)
    {
        $this->user_stories_to_add    = $user_stories_to_add;
        $this->user_stories_to_remove = $user_stories_to_remove;
        $this->artifact_link_field    = $artifact_link_field;
    }

    public function getFieldDataForChangesetCreationFormat(int $milestone_project_id): array
    {
        $fields_data                                               = [];
        $fields_data[$this->artifact_link_field]['new_values']     = implode(",", $this->getFeatureChangeToAdd($this->user_stories_to_add, $milestone_project_id));
        $fields_data[$this->artifact_link_field]['removed_values'] =
            $this->getFeatureChangeToRemove($this->user_stories_to_remove);

        return $fields_data;
    }

    /**
     * @param FeatureChange[] $feature_changes
     * @return int[]
     */
    private function getFeatureChangeToAdd(array $feature_changes, int $milestone_project_id): array
    {
        $feature_to_add = [];
        foreach ($feature_changes as $feature_change) {
            if ($feature_change->project_id === $milestone_project_id) {
                $feature_to_add[] = $feature_change->id;
            }
        }
        return $feature_to_add;
    }

    /**
     * @param FeatureChange[] $feature_changes
     * @psalm-return array<int|string, int|string>
     */
    private function getFeatureChangeToRemove(
        array $feature_changes,
    ): array {
        $user_stories_to_remove = [];
        foreach ($feature_changes as $user_story_id) {
            if (! in_array($user_story_id->id, array_column($this->user_stories_to_add, "id"), true)) {
                $user_stories_to_remove[$user_story_id->id] = $user_story_id->id;
            }
        }

        return $user_stories_to_remove;
    }
}
