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

/**
 * @psalm-immutable
 */
final class FieldData
{
    /**
     * @var array
     */
    private $user_stories_to_add;
    /**
     * @var array
     */
    private $user_stories_to_remove;
    /**
     * @var int
     */
    private $artifact_link_field;

    public function __construct(array $user_stories_to_add, array $user_stories_to_remove, int $artifact_link_field)
    {
        $this->user_stories_to_add    = $user_stories_to_add;
        $this->user_stories_to_remove = $user_stories_to_remove;
        $this->artifact_link_field    = $artifact_link_field;
    }

    public function getFieldDataForChangesetCreationFormat(): array
    {
        $fields_data                                               = [];
        $fields_data[$this->artifact_link_field]['new_values']     = implode(",", $this->user_stories_to_add);
        $fields_data[$this->artifact_link_field]['removed_values'] =
            $this->getUserStoriesThatAreLinkedToMilestoneAndNoLongerInArtifactLinkList($this->user_stories_to_remove);

        return $fields_data;
    }

    /**
     * @return array
     */
    private function getUserStoriesThatAreLinkedToMilestoneAndNoLongerInArtifactLinkList(
        array $user_stories_linked_to_milestones
    ): array {
        $user_stories_to_remove = [];
        foreach ($user_stories_linked_to_milestones as $key => $value) {
            if (! in_array($key, $this->user_stories_to_add, true)) {
                $user_stories_to_remove[$key] = $key;
            }
        }

        return $user_stories_to_remove;
    }
}
