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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\ProgramManagement\Domain\Team\MirroredMilestone\MirroredMilestone;

class UserStoriesLinkedToMilestoneBuilder
{
    /**
     * @var ArtifactsLinkedToParentDao
     */
    private $user_stories_linked_to_milestones_dao;

    public function __construct(ArtifactsLinkedToParentDao $user_stories_linked_to_milestones_dao)
    {
        $this->user_stories_linked_to_milestones_dao = $user_stories_linked_to_milestones_dao;
    }

    /**
     * @return int[]
     */
    public function build(MirroredMilestone $milestone): array
    {
        $potential_user_stories = $this->user_stories_linked_to_milestones_dao->getUserStoriesOfMirroredMilestone($milestone->getId());

        $user_stories = [];
        foreach ($potential_user_stories as $unlink) {
            if (
                ! $this->user_stories_linked_to_milestones_dao->isLinkedToASprintInMirroredMilestones(
                    $unlink['id'],
                    $unlink['release_tracker_id'],
                    $unlink['project_id']
                )
            ) {
                $user_stories[$unlink['id']] = $unlink['id'];
            }
        }

        return $user_stories;
    }
}
