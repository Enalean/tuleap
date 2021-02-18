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

use Tuleap\ProgramManagement\Team\MirroredMilestone\MirroredMilestone;

class FeaturesLinkedToMilestoneBuilder
{
    /**
     * @var FeaturesLinkedToMilestonesDao
     */
    private $features_linked_to_milestones_dao;

    public function __construct(FeaturesLinkedToMilestonesDao $features_linked_to_milestones_dao)
    {
        $this->features_linked_to_milestones_dao = $features_linked_to_milestones_dao;
    }

    /**
     * @return int[]
     */
    public function build(MirroredMilestone $milestone, int $program_increment_id): array
    {
        $feature_to_unlink = $this->features_linked_to_milestones_dao->getFeaturesLinkedToMilestone($milestone->getId(), $program_increment_id);

        $fetaure_to_unlink = [];
        foreach ($feature_to_unlink as $unlink) {
            $fetaure_to_unlink[$unlink['id']] = 1;
        }

        return $fetaure_to_unlink;
    }
}
