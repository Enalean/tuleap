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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\SearchUserStoryPlannedInIteration;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifier;

final class IterationContentDAO extends DataAccessObject implements SearchUserStoryPlannedInIteration
{
    #[\Override]
    public function searchStoriesOfMirroredIteration(MirroredIterationIdentifier $mirrored_iteration_identifier): array
    {
        $sql = "SELECT user_story.id
                    FROM tracker_artifact AS mirrored_iteration
                     INNER JOIN plugin_agiledashboard_planning
                                ON planning_tracker_id =  mirrored_iteration.tracker_id
                    INNER JOIN plugin_agiledashboard_planning_backlog_tracker ON planning_id = plugin_agiledashboard_planning.id
                    INNER JOIN tracker AS tracker_mirrored_iteration
                               ON (
                                           mirrored_iteration.tracker_id = tracker_mirrored_iteration.id
                                       AND tracker_mirrored_iteration.deletion_date IS NULL
                                   )
                    INNER JOIN tracker_field AS f
                               ON (tracker_mirrored_iteration.id = f.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value AS tcv
                               ON (tcv.changeset_id = mirrored_iteration.last_changeset_id AND tcv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS mirrored_iteration_link
                               ON mirrored_iteration_link.changeset_value_id = tcv.id
                    INNER JOIN tracker_artifact AS user_story
                               ON (mirrored_iteration_link.artifact_id = user_story.id AND user_story.tracker_id = plugin_agiledashboard_planning_backlog_tracker.tracker_id)
            WHERE mirrored_iteration.id = ?";

        return $this->getDB()->first($sql, $mirrored_iteration_identifier->getId());
    }
}
